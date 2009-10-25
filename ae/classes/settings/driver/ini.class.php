<?php
/**
 * Settings library ini driver file
 *
 * See {@link AeSettings_Driver_Ini} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Settings library ini driver
 *
 * This driver allows to load/save settings using ini files as storage. See
 * {@link AeSettings_Driver_Ini::get()} and {@link AeSettings_Driver_Ini::set()}
 * for more information.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeSettings_Driver_Ini extends AeSettings_Driver
{
    /**
     * Ini file name and path
     * @var string
     */
    protected $_filename = null;

    const EXTENSION = 'ini';

    /**
     * Ini driver constructor
     *
     * This is to be used internally by {@link AeSettings::getInstance()},
     * direct use is discouraged.
     *
     * If no setting data is provided, settings are not loaded. They should be
     * loaded later using {@link AeSettings_Driver_Ini::load()} method.
     *
     * For the ini settings driver, setting data is a single string, containig
     * ini file path and name (including the *.ini extension part). See {@link
     * AeSettings_Driver_Ini::load()} for more information on loading ini
     * settings.
     *
     * @throws AeSettingsDriverIniException #406 if invalid data is passed
     *
     * @param string $data settings file path
     */
    public function __construct($data = null)
    {
        if ($data !== null)
        {
            $this->_filename = $data;

            if (!$this->load()) {
                throw new AeSettingsDriverIniException('Could not load settings from file ' . $data, 406);
            }
        }
    }

    /**
     * Load settings
     *
     * Load settings using file path provided. A file path can be an absolute
     * path, relative path to AnEngine root directory (the one with index.php in
     * it) or relative path to include path. File path must contain the target
     * file name including extension.
     *
     * @param string $data setting file path. Defaults to current {@link
     *                     AeSettings_Driver_Ini::_filename} property value
     *
     * @return bool true on success, false otherwise
     */
    public function load($data = null)
    {
        $data = $data === null ? $this->_filename : $data;

        // *** Check extension and add if required
        if (!strpos($data, '.')) {
            $data .= '.' . self::EXTENSION;
        }

        if ($data === null || !file_exists($data) || !is_readable($data)) {
            return false;
        }

        $settings = @parse_ini_file($data, true);

        if ($settings && count($settings) > 0)
        {
            foreach ($settings as $section => $values)
            {
                if (count($values) > 0)
                {
                    $values = $this->_stringToValue($values);

                    foreach ($values as $name => $value) {
                        $this->set($section . '.' . $name, $value);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Save settings
     *
     * Save settings using file path provided. A file path can be an absolute
     * path, relative path to AnEngine root directory (the one with index.php in
     * it) or relative path to include path. File path must contain the target
     * file name including extension.
     *
     * If the file path is not passed, current {@linkAeSettings_Driver_Ini::_filename}
     * property value will be used. If the latter is not set either, the file
     * will be created in the AnEngine root dir, with {@link
     * AeSettings_Driver_Ini::_section} plus .ini extension as its filename.
     *
     * Any multi-dimensional arrays, set via advanced <var>$name</var> usage of
     * the {@link AeSettings_Driver_Ini::set()} method, will be written using
     * the dot as the array key separator:
     * <code> $params->set('section.foo.bar', 'baz');
     * $params->save('section.ini');</code>
     *
     * The above code will produce something like this in the ini file:
     * <pre> [section]
     * ; foo array
     * foo.bar = baz</pre>
     *
     * Each file also contains generator information, which includes:
     * - absolute file path
     * - generation date
     * - generator class (in case used driver is a child of this class)
     *
     * @param string $data setting file path
     *
     * @return bool true on success, false otherwise
     */
    public function save($data = null)
    {
        if ($data === null) {
            $data = $this->_filename === null ? $this->_section : $this->_filename;
        }

        // *** Check extension and add if required
        if (!strpos($data, '.')) {
            $data .= '.' . self::EXTENSION;
        }

        if ($data === null || (file_exists($data) && !is_writable($data))) {
            return false;
        }

        $file = AeFile::getInstance('file', $data);

        if (!$file->exists()) {
            $file->create();
        } else {
            $file->clear();
        }

        $file->append('; File generated automatically' . "\n");
        $file->append('; Path: ' . $file->getPath() . "\n");
        $file->append('; Date: ' . date('r') . "\n");
        $file->append('; Generator: ' . $this->getClass() . "\n");

        if (count($this->_properties) > 0)
        {
            foreach ($this->_properties as $section => $values)
            {
                if (count($values) > 0) {
                    $file->append("\n" . '[' . $section . ']' . "\n");
                    $file->append($this->_valueToString($values));
                }
            }
        }

        return true;
    }

    /**
     * Convert string to value
     *
     * Convert passed string to a valid data structure. Used to parse strings
     * created with {@link AeSettings_Driver_Ini::_valueToString()} method.
     *
     * @param mixed $value value to parse
     *
     * @return mixed
     */
    protected function _stringToValue($value)
    {
        if (is_array($value))
        {
            if (count($value) == 0) {
                return $value;
            }

            $return = array();

            foreach ($value as $key => $val)
            {
                $val = str_replace('&quot;', '"', $val);
                $val = str_replace('&amp;' , '&', $val);

                if (substr($val, 0, 10) == 'serialize:')
                {
                    list($m, $l, $s) = explode(':', $val, 3);

                    if (strlen($s) == (int) $l) {
                        $val = unserialize($s);
                    }
                }

                $this->_setByKey($key, $val, $return);
            }

            return $return;
        }

        return true;
    }

    /**
     * Convert value to string
     *
     * Convert passed value to a valid ini string
     *
     * @param array  $values an associative array of values
     * @param string $sub    internal subsection value
     *
     * @return string
     */
    protected function _valueToString($values, $sub = '')
    {
        $return = '';

        foreach ($values as $key => $value)
        {
            $string = false;

            if (is_numeric($value)) {
                $string = $sub . $key . ' = ' . $value;
            } else if (is_string($value)) {
                $value  = str_replace('&', '&amp;' , $value);
                $value  = str_replace('"', '&quot;', $value);
                $string = $sub . $key . ' = "' . $value . '"';
            } else if (is_bool($value)) {
                $string = $sub . $key . ' = ' . ($value ? 'true' : 'false');
            } else if (is_null($value)) {
                $string = $sub . $key . ' = null';
            } else if (is_array($value)) {
                $return .= "\n" . '; ' . $sub . $key . ' array' . "\n";
                $return .= $this->_valueToString($value, $sub . $key . '.');
            } else if (is_object($value)) {
                $value  = serialize($value);
                $length = strlen($value);
                $value  = str_replace('&', '&amp;' , $value);
                $value  = str_replace('"', '&quot;', $value);
                $string = $sub . $key . ' = "serialize:' . $length . ':' . $value . '"';
            } else {
                continue;
            }

            if ($string !== false) {
                $return .= $string . "\n";
            }
        }

        return $return;
    }
}

/**
 * Settings ini driver exception class
 *
 * Settings ini driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSettingsDriverIniException extends AeSettingsDriverException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Ini');
        parent::__construct($message, $code);
    }
}
?>