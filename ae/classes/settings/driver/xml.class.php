<?php
/**
 * Settings library XML driver file
 *
 * See {@link AeSettings_Driver_Xml} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Settings library XML driver
 *
 * This driver allows to load/save settings using XML files as storage. See
 * {@link AeSettings_Driver_Xml::get()} and {@link AeSettings_Driver_Xml::set()}
 * for more information.
 *
 * Settings are loaded using {@link AeXml} class.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeSettings_Driver_Xml extends AeSettings_Driver
{
    /**
     * XML file name and path
     * @var string
     */
    protected $_filename = null;

    const EXTENSION = 'xml';

    /**
     * XML driver constructor
     *
     * This is to be used internally by {@link AeSettings::getInstance()},
     * direct use is discouraged.
     *
     * If no setting data is provided, settings are not loaded. They should be
     * loaded later using {@link AeSettings_Driver_Xml::load()} method.
     *
     * For the xml settings driver, setting data is a single string, containig
     * xml file path and name (including the *.xml extension part). See {@link
     * AeSettings_Driver_Xml::load()} for more information on loading xml settings.
     *
     * @throws AeSettingsDriverXmlException #406 if invalid data is passed
     *
     * @param string $data settings file path
     */
    public function __construct($data = null)
    {
        if ($data !== null)
        {
            $this->_filename = $data;

            if (!$this->load()) {
                throw new AeSettingsDriverXmlException('Could not load settings from file ' . $data, 406);
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
     *                     AeSettings_Driver_Xml::_filename} property value
     *
     * @return bool true on success, false otherwise
     */
    public function load($data = null)
    {
        $data = $data === null ? $this->_filename : $data;

        // *** Check extension and add if required
        if (strpos($data, '.') === false) {
            $data .= '.' . self::EXTENSION;
        }

        if ($data === null || !file_exists($data) || !is_readable($data)) {
            return false;
        }

        $xml = AeXml::getContents($data);

        if ($xml->hasChildren())
        {
            foreach ($xml->getChildren() as $section) {
                $this->_properties[$section->getName()] = $this->_stringToValue($section);
            }
        }

        return $this;
    }

    /**
     * Save settings
     *
     * Save settings using file path provided. A file path can be an absolute
     * path, relative path to AnEngine root directory (the one with index.php in
     * it) or relative path to include path. File path must contain the target
     * file name including extension.
     *
     * If the file path is not passed, current {@link AeSettings_Driver_Xml::_filename}
     * property value will be used. If the latter is not set either, the file
     * will be created in the AnEngine root dir, with {@link
     * AeSettings_Driver_Xml::_section} plus .xml extension as its filename.
     *
     * Any multi-dimensional arrays, set via advanced <var>$name</var> usage of
     * the {@link AeSettings_Driver_Xml::set()} method, will be written as a
     * multi-dimensional array:
     * <code> $params->set('section.foo.bar', 'baz');
     * $params->save('section.xml');</code>
     *
     * The above code will produce something like this in the xml file:
     * <code> <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
     * <settings path="section.xml" date="Sun, 29 Nov 2009 05:48:11 +0200" generator="AeSettings_Driver_Xml">
     *     <section>
     *         <foo>
     *             <bar type="string">baz</bar>
     *         </foo>
     *     </section>
     * </settings></code>
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
        if (strpos($data, '.') === false) {
            $data .= '.' . self::EXTENSION;
        }

        if ($data === null || (file_exists($data) && !is_writable($data))) {
            return false;
        }

        $data = AeFile::absolutePath($data);
        $xml  = AeXml::element('settings');

        $xml->setAttributes(array(
            'path' => $data,
            'date' => date('r'),
            'generator' => $this->getClass()
        ));

        if (count($this->_properties) > 0)
        {
            foreach ($this->_properties as $section => $settings) {
                $this->_valueToString($section, $settings, $xml);
            }
        }

        $xml->save($data);

        return $this;
    }

    /**
     * Convert value to string
     *
     * Convert passed value to a valid php string
     *
     * @param string     $name
     * @param mixed      $value
     * @param AeXml_Node $node
     *
     * @return string
     */
    protected function _valueToString($name, $value, AeInterface_Xml_Element $element)
    {
        if ($value instanceof AeType) {
            $value = $value->getValue();
        }

        if (is_numeric($name)) {
            $child = $element->addChild('key')->setAttributes(array(
                'key-name' => $name,
                'key-type' => AeType::of($name)
            ));
        } else {
            $child = $element->addChild($name);
        }

        if (is_array($value))
        {
            foreach ($value as $k => $v) {
                $this->_valueToString($k, $v, $child);
            }
        } else if (is_object($value)) {
            $child->setAttribute('type', 'object');

            $value = serialize($value);
            $value = str_replace('&', '&amp;', $value);
            $value = str_replace(chr(0), '&null;', $value);

            $child->setData($value);
        } else {
            $child->setAttribute('type', AeType::of($value));

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            if (!is_null($value)) {
                $child->setData($value);
            }
        }

        return $child;
    }

    /**
     * Convert string to value
     *
     * Convert passed string to a valid data structure. Used to parse strings
     * created with {@link AeXml_Node::write()} method.
     *
     * @param AeXml_Node $value value to parse
     *
     * @return mixed
     */
    protected function _stringToValue(AeInterface_Xml_Element $element)
    {
        $type = $element->getAttribute('type', 'array');

        if ($type == 'array')
        {
            $return = array();

            foreach ($element->getChildren() as $child) {
                $name = $child->getName();

                if ($name == 'key' && $child->hasAttribute('key-name')) {
                    $name  = $child->getAttribute('key-name', $name);
                    $ntype = $child->getAttribute('key-type', 'string');
                    $name  = $ntype == 'integer' ? (int) $name : $name;
                }

                $return[$name] = $this->_stringToValue($child);
            }
        } else if ($type == 'object') {
            $value  = $element->getData();
            $value  = str_replace('&null;', chr(0), $value);
            $value  = str_replace('&amp;', '&', $value);
            $return = unserialize($value);
        } else {
            $value  = (string) $element->getData();
            $return = null;

            switch ($type)
            {
                case 'boolean': {
                    $return = $value == 'true' ? true : false;
                } break;

                case 'integer': {
                    $return = (int) $value;
                } break;

                case 'float': {
                    $return = (float) $value;
                } break;

                case 'string': {
                    $return = (string) $value;
                } break;

                case 'null': {
                    $return = null;
                } break;
            }
        }

        return $return;
    }
}

/**
 * Settings XML driver exception class
 *
 * Settings XML driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSettingsDriverXmlException extends AeSettingsDriverException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Xml');
        parent::__construct($message, $code);
    }
}
?>