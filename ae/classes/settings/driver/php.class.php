<?php
/**
 * Settings library php driver file
 *
 * See {@link AeSettings_Driver_Php} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Settings library php driver
 *
 * This driver allows to load/save settings using php files as storage. See
 * {@link AeSettings_Driver_Php::get()} and {@link AeSettings_Driver_Php::set()}
 * for more information.
 *
 * Settings are loaded using simple {@link include} construct. This means less
 * parsing time, and also inability to read your config files directly using web
 * browser. However, make sure your config files cannot be included by any other
 * scripts, as they will be able to read any settings, contained within. Best
 * practice is saving scripts in a NON-web-accessible location.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeSettings_Driver_Php extends AeSettings_Driver
{
    /**
     * Php file name and path
     * @var string
     */
    protected $_filename = null;

    const EXTENSION = 'php';

    /**
     * Php driver constructor
     *
     * This is to be used internally by {@link AeSettings::getInstance()},
     * direct use is discouraged.
     *
     * If no setting data is provided, settings are not loaded. They should be
     * loaded later using {@link AeSettings_Driver_Php::load()} method.
     *
     * For the php settings driver, setting data is a single string, containig
     * php file path and name (including the *.php extension part). See {@link
     * AeSettings_Driver_Php::load()} for more information on loading php
     * settings.
     *
     * @throws AeSettingsDriverPhpException #406 if invalid data is passed
     *
     * @param string $data settings file path
     */
    public function __construct($data = null)
    {
        if ($data !== null)
        {
            $this->_filename = $data;

            if (!$this->load()) {
                throw new AeSettingsDriverPhpException('Could not load settings from file ' . $data, 406);
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
     *                     AeSettings_Driver_Php::_filename} property value
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

        $s = array();

        include $data;

        if ($s && count($s) > 0)
        {
            foreach ($s as $section => $values)
            {
                if (count($values) > 0)
                {
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
     * If the file path is not passed, current {@link AeSettings_Driver_Php::_filename}
     * property value will be used. If the latter is not set either, the file
     * will be created in the AnEngine root dir, with {@link
     * AeSettings_Driver_Php::_section} plus .php extension as its filename.
     *
     * Any multi-dimensional arrays, set via advanced <var>$name</var> usage of
     * the {@link AeSettings_Driver_Php::set()} method, will be written as a
     * multi-dimensional array:
     * <code> $params->set('section.foo.bar', 'baz');
     * $params->save('section.php');</code>
     *
     * The above code will produce something like this in the php file:
     * <code> if (!defined('SLASH')) die;
     *
     * $s = array();
     *
     * // *** Section section
     * $s['section'] = array();
     *
     * $s['section']['foo'] = array (
     *   'bar' => 'bar',
     * );</code>
     *
     * Each file also contains generator information, which includes:
     * - absolute file path
     * - generation date
     * - generator class (in case used driver is a child of this class)
     * - package AnEngine (for use with phpDocumentor) and subpackage Runtime,
     *   to distinct internal files with auto-generated config files
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

        $file->append('<' . '?php' . "\n");
        $file->append('/**' . "\n");

        $file->append(' * File generated automatically' . "\n");
        $file->append(' *' . "\n");
        $file->append(' * Path: ' . $file->getPath() . "\n");
        $file->append(' * Date: ' . date('r') . "\n");
        $file->append(' * Generator: {@link ' . $this->getClass() . "}\n");
        $file->append(' *' . "\n");

        $file->append(' * @package AnEngine' . "\n");
        $file->append(' * @todo add subpackage once custom documentor is done //Runtime' . "\n");
        $file->append(' */' . "\n");
        $file->append('if (!defined(\'SLASH\')) die;' . "\n");

        $file->append("\n" . '$s = array();' . "\n");

        if (count($this->_properties) > 0)
        {
            foreach ($this->_properties as $section => $values)
            {
                if (count($values) > 0)
                {
                    $_section = var_export($section, true);

                    $file->append("\n" . '// *** Section ' . $section . "\n");
                    $file->append('$s[' . $_section . '] = array();' . "\n\n");

                    foreach ($values as $key => $value) {
                        $_key = var_export($key, true);

                        $file->append('$s[' . $_section . '][' . $_key . '] = ' . var_export($this->_valueToString($value), true) . ';' . "\n");
                    }
                }
            }
        }

        $file->append("\n" . '?' . '>');

        return true;
    }

    /**
     * Convert value to string
     *
     * Convert passed value to a valid php string
     *
     * @param array  $values an associative array of values
     *
     * @return string
     */
    protected function _valueToString($value)
    {
        if (is_array($value))
        {
            foreach ($value as $key => $val) {
                $value[$key] = $this->_valueToString($val);
            }
        } else if (is_object($value)) {
            $class = get_class($value);

            if (!method_exists($class, '__set_state'))
            {
                $object = new AeNode;
            } else {
                $object = $value;
            }

            foreach ($value as $key => $val) {
                $object->set($key, $this->_valueToString($val));
            }

            $value = $object;
        }

        return $value;
    }
}

/**
 * Settings php driver exception class
 *
 * Settings php driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSettingsDriverPhpException extends AeSettingsDriverException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Php');
        parent::__construct($message, $code);
    }
}
?>