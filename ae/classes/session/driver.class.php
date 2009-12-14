<?php
/**
 * Session driver class file
 *
 * See {@link AeSession_Driver} class documentation
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Session driver class
 *
 * This is a basis for all the session connection drivers in the framework
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeSession_Driver extends AeObject implements AeInterface_Session
{
    const DEFAULT_DRIVER = 'file';
    protected $_registered = false;

    /**
     * Get instance
     *
     * Returns an instance of the session connection driver. A session
     * connection driver is an object, which handles reading, writing, removing
     * and garbage collection of the session data
     *
     * @param string  $driver  driver name
     * @param AeArray $options driver options array. See individual driver
     *                         documentation for the list of available options
     *
     * @return AeInterface_Session
     */
    public static function getInstance($driver = null, AeArray $options = null)
    {
        $driver = $driver !== null ? $driver : self::DEFAULT_DRIVER;
        $class  = 'AeSession_Driver_' . ucfirst($driver);
        $args   = func_get_args();
        $args   = array_slice($args, 1);

        try {
            $instance = AeInstance::get($class, $args, true, false);
        } catch (AeInstanceException $e) {
            if ($e->getCode() == 404) {
                throw new AeSessionDriverException(ucfirst($driver) . ' driver not found', 404);
            }

            throw $e;
        }

        if (!($instance instanceof AeInterface_Session)) {
            throw new AeSessionDriverException(ucfirst($driver) . ' driver has an invalid access interface', 501);
        }

        return $instance;
    }

    /**
     * Constructor
     *
     * Registers the session connection driver as current session handler
     *
     * @uses AeSession_Driver::register()
     *
     * @param AeArray $options
     */
    public function __construct(AeArray $options = null)
    {
        $this->register();
    }

    /**
     * Register session connection driver
     *
     * Registers the session connection driver as current session handler
     *
     * See {@link http://php.net/session_set_save_handler
     * session_set_save_handler()} for more details
     *
     * @return bool
     */
    public function register()
    {
        if ($this->_registered !== true)
        {
            $this->_registered = @session_set_save_handler(
                array($this, 'open'),
                array($this, 'close'),
                array($this, 'read'),
                array($this, 'write'),
                array($this, 'destroy'),
                array($this, 'clean')
            );
        }

        return $this->_registered;
    }

    /**
     * Open method
     *
     * This works like a constructor in classes and is executed when the session
     * is being opened. The open function expects two parameters, where the
     * first is the save path and the second is the session name
     *
     * @param string $path
     * @param string $name
     *
     * @return bool
     */
    public function open($path, $name)
    {
        return true;
    }

    /**
     * Close method
     *
     * This works like a destructor in classes and is executed when the session
     * operation is done
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }
}

/**
 * Session driver exception class
 *
 * Session driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSessionDriverException extends AeSessionException
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