<?php
/**
 * Settings library file
 *
 * See {@link AeSettings} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Settings library
 *
 * This is a basic settings wrapper. It simplifies loading/saving and using
 * configuration files throughout the framework. Using it is very simple:
 *
 * <code> $params = AeSettings::getInstance('ini');
 * $params->load('mysettings.ini'); // Assuming it's in the framework root
 *
 * echo $params->get('myecho', 'Hello World!');
 *
 * // Set the section explicitly, works same as above
 * echo $params->get('default.myecho', 'Hello World!');</code>
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeSettings
{
    const DEFAULT_DRIVER = 'ini';

    /**
     * Get settings object
     *
     * See {@link AeSettings} documentation for details
     *
     * @throws AeSettingsException #404 if driver not found
     * @throws AeSettingsException #501 if driver is not an implementation of
     *                             the {@link AeInterface_Settings} interface
     *
     * @param string $driver  driver name
     * @param mixed  $arg,... unlimited number of arguments to pass to the driver
     *
     * @return AeInterface_Settings instance of a selected settings driver
     */
    public static function getInstance($driver = null)
    {
        $driver = (string) $driver;

        if (strpos($driver, '.') && file_exists($driver)) {
            // *** This is definitely a file path
            $args   = array($driver);
            $driver = strtolower(substr(strrchr($driver, '.'), 1));
        } else {
            $driver = $driver !== null ? $driver : self::DEFAULT_DRIVER;
            $args   = func_get_args();
            $args   = array_splice($args, 1);
        }

        $class  = 'AeSettings_Driver_' . ucfirst($driver);

        try {
            $instance = AeInstance::get($class, $args);
        } catch (AeInstanceException $e) {
            if ($e->getCode() == 404) {
                throw new AeSettingsException(ucfirst($driver) . ' driver not found', 404);
            }

            throw $e;
        }

        if (!($instance instanceof AeInterface_Settings)) {
            throw new AeSettingsException(ucfirst($driver) . ' driver has an invalid access interface', 501);
        }

        return $instance;
    }
}

/**
 * Settings exception class
 *
 * Settings-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSettingsException extends AeLibraryException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Settings');
        parent::__construct($message, $code);
    }
}
?>