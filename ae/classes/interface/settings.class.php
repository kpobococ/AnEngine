<?php
/**
 * Settings interface file
 *
 * See {@link AeInterface_Settings} interface documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */

/**
 * Settings interface
 *
 * This is a common settings driver interface. All settings drivers must
 * implement it.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */
interface AeInterface_Settings
{
    /**
     * Driver constructor
     *
     * This is to be used internally by {@link AeSettings::getInstance()},
     * direct use is discouraged.
     *
     * If no setting data is provided, settings are not loaded. They should be
     * loaded later using {@link AeInterface_Settings::load()} method.
     * 
     * Setting data can be different depending on the driver used. For example,
     * file-based drivers, like Ini, would use it as settings file name, while
     * database-based drivers would use it as some filtering parameter in the
     * database, or even a table name.
     *
     * @param string $data driver-specific setting data
     */
    public function __construct($data = null);

    /**
     * Get setting value
     *
     * Return setting value or default value, if setting not found:
     * <code> $params->get('foo.bar');</code>
     *
     * @param string $name    setting name
     * @param mixed  $default default value
     *
     * @return AeType setting value
     */
    public function get($name, $default = null);

    /**
     * Set setting value
     *
     * Set the setting value to the one specified. If a setting with the name
     * specified is not found, it will be created:
     * <code> $params->set('foo.bar', 'baz');</code>
     *
     * @param string $name  setting name
     * @param mixed  $value new setting value
     *
     * @return bool true on success, false otherwise
     */
    public function set($name, $value);

    /**
     * Clear setting value
     *
     * Clear the setting value and return the former value. If a setting with the
     * name specified is not found, null is returned.
     *
     * @param string $name setting name
     *
     * @return AeType former setting value
     */
    public function clear($name);

    /**
     * Get section
     *
     * Returns current default section, or section values, if <var>$name</var>
     * is specified
     *
     * @param string $name
     *
     * @return string|array
     */
    public function getSection($name = null);

    /**
     * Set section
     *
     * Sets current default section, or section values, if <var>$name</var> is
     * specified. Return previous default section or previous section values
     * respectively
     *
     * @param string $name
     * @param array  $values
     *
     * @return string|array
     */
    public function setSection($name, $values = null);

    /**
     * Clear section
     *
     * Clears all section values. Returns cleared values
     *
     * @param string $name
     *
     * @return array
     */
    public function clearSection($name);

    /**
     * Load settings
     *
     * Load settings using setting data provided. See {@link
     * AeInterface_Settings::__construct()} for more info on setting data.
     *
     * @param string $data driver-specific setting data
     *
     * @return bool true on success, false otherwise
     */
    public function load($data = null);

    /**
     * Save settings
     *
     * Save settings using setting data provided. If you want to create a new
     * settings ini file, for example:
     * <code> $params = AeSettings::getInstance('ini', 'myfile.ini');
     *
     * // Set some settings
     * $params->set('default.foo', 'bar');
     * // ...
     *
     * $params->save(); // Save</code>
     *
     * @param string $data driver-specific setting data
     *
     * @return bool true on success, false otherwise
     */
    public function save($data = null);
}

?>