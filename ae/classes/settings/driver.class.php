<?php
/**
 * Settings library driver file
 *
 * See {@link AeSettings_Driver} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Settings library driver
 *
 * A settings driver class simplifies an implementation of specific drivers by
 * implementing the basic logic in all applicable methods. It is recommended to
 * derive all specific database driver classes from this class. However, it is
 * only required to implement the {@link AeInterface_Settings} interface for any
 * custom settings driver.
 *
 * If a custom driver is not an implementation of the {@link
 * AeInterface_Settings}, an exception will be thrown by {@link AeSettings}. See
 * {@link AeSettings::getInstance()} for more details.
 *
 * @method bool  setSettings() setSettings(array $settings) Set the settings
 *                             array to a specific value
 * @method mixed getSettings() getSettings(mixed $default) Get the whole
 *                             settings array
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeSettings_Driver extends AeNode_Nested implements AeInterface_Settings
{
    /**
     * Name matching pattern
     *
     * Failure to match a setting name to this pattern will trigger an exception
     *
     * @var string
     */
    protected $_namePattern = '#^[_a-z][._a-z0-9]*$#i';

    /**
     * Section name
     *
     * @var string
     */
    protected $_section = 'default';

    /**
     * Driver constructor
     *
     * This is to be used internally by {@link AeSettings::getInstance()},
     * direct use is discouraged.
     *
     * If no setting data is provided, settings are not loaded. They should be
     * loaded later using {@link AeSettings_Driver::load()} method.
     *
     * Setting data can be different depending on the driver used. For example,
     * file-based drivers, like Ini, would use it as settings file name, while
     * database-based drivers would use it as some filtering parameter in the
     * database, or even a table name.
     *
     * @throws AeSettingsDriverException #406 if invalid data is passed
     *
     * @param string $data driver-specific setting data
     */
    public function __construct($data = null)
    {
        if ($data !== null)
        {
            $data = (string) $data;

            if (!$this->load($data)) {
                throw new AeSettingsDriverException('Could not load settings from data', 500);
            }
        }
    }

    /**
     * Get setting value
     *
     * Return setting value or default value, if setting not found:
     * <code> $params->get('foo.bar', 'baz');</code>
     *
     * @uses AeSettings_Driver::$_namePattern
     *
     * @throws AeSettingsDriverException #400 if setting name is invalid
     *
     * @param string $name    setting name
     * @param mixed  $default default value
     *
     * @return AeType|mixed setting value or default value
     */
    public function get($name, $default = null)
    {
        $name = (string) $name;

        if ($this->propertyExists($name)) {
            // *** Never wrap object properties
            return parent::get($name, $default);
        }

        if (!preg_match($this->_namePattern, (string) $name)) {
            throw new AeSettingsDriverException('Setting name is invalid', 400);
        }

        if (strpos($name, '.')) {
            list ($section, $name) = explode('.', $name, 2);
        } else {
            $section = $this->section;
        }

        return AeType::wrapReturn(parent::get($section.'.'.$name, $default));
    }

    /**
     * Set setting value
     *
     * Set the setting value to the one specified. If a setting with the name
     * specified is not found, it will be created:
     * <code> $params->set('foo.bar', 'baz');</code>
     *
     * @uses AeSettings_Driver::$_namePattern
     *
     * @throws AeSettingsDriverException #400 if setting name is invalid
     *
     * @param string $name  setting name
     * @param mixed  $value new setting value
     *
     * @return bool true on success, false otherwise
     */
    public function set($name, $value)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::set($name, $value);
        }

        if (!preg_match($this->_namePattern, $name)) {
            throw new AeSettingsDriverException('Setting name is invalid', 400);
        }

        if (strpos($name, '.')) {
            list ($section, $name) = explode('.', $name, 2);
        } else {
            $section = $this->section;
        }

        return parent::set($section.'.'.$name, $value);
    }

    /**
     * Clear setting value
     *
     * Clear the setting value and return former value. If a setting with the
     * name specified is not found, null is returned.
     *
     * @uses AeSettings_Driver::$_namePattern
     *
     * @throws AeSettingsDriverException #400 if setting name is invalid
     *
     * @param string $name setting name
     *
     * @return AeType|mixed former setting value
     */
    public function clear($name)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::clear($name);
        }

        if (!preg_match($this->_namePattern, $name)) {
            throw new AeSettingsDriverException('Setting name is invalid', 400);
        }

        if (strpos($name, '.')) {
            list ($section, $name) = explode('.', $name, 2);
        } else {
            $section = $this->section;
        }

        return AeType::wrapReturn(parent::clear($section.'.'.$name));
    }

    public function getSection($name = null)
    {
        if ($name === null) {
            return $this->_section;
        }

        $name = (string) $name;

        return AeType::wrapReturn(isset($this->_properties[$name]) ? $this->_properties[$name] : array());
    }

    public function setSection($name, $values = null)
    {
        $name = (string) $name;

        if ($values === null) {
            $return         = $this->_section;
            $this->_section = $name;
            return $return;
        }

        $return = $this->getSection($name);

        if ($values instanceof AeArray) {
            $values = $values->getValue();
        }

        if (is_array($values))
        {
            $this->_properties[$name] = array();

            foreach ($values as $key => $value) {
                $this->set($name.'.'.$key, $value);
            }
        }

        return $return;
    }

    public function clearSection($name)
    {
        $name   = (string) $name;
        $return = $this->getSection($name);

        $this->setSection($name, array());

        return $return;
    }
}

/**
 * Settings driver exception class
 *
 * Settings driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSettingsDriverException extends AeSettingsException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Driver');
        parent::__construct($message, $code);
    }
}
?>