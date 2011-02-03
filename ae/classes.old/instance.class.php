<?php
/**
 * Instance class file
 *
 * See {@link AeInstance} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Instance class
 *
 * This is an instance control file. AnEngine has a lot of classes in its
 * framework that match a singleton pattern. This class is a centralized storage
 * for all the singleton class instances. It also helps instantiate the classes.
 *
 * See {@link AeInstance::get()} for more details.
 *
 * @todo consider making AeInstance instance-based, not static
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeInstance
{
    protected static $_instances = array();

    /**
     * Get a class instance
     *
     * Return an instance of a <var>$class</var> class using <var>$args</var>
     * parameters for the constructor. An {@link md5()} hash of the class name
     * and serialized arguments will be used as a class key for storage.
     *
     * @param string $class     class name
     * @param array  $args      class constructor arguments array
     * @param bool   $save      should the class instance be saved or not
     * @param bool   $useGetter should the getInstance method be used, if
     *                          present
     * 
     * @return object
     */
    public static function get($class, $args = array(), $save = true, $useGetter = null)
    {
        $key = self::generateKey($class, $args);

        if (!isset(self::$_instances[$key]))
        {
            if (!class_exists($class)) {
                throw new AeInstanceException(ucfirst($class) . ' class not found', 404);
            }

            if (method_exists($class, 'getInstance'))
            {
                if ($useGetter === null)
                {
                    // *** Check to prevent an infinite loop
                    $trace     = debug_backtrace();
                    $trace     = array_slice($trace, 1);
                    $useGetter = true;

                    foreach ($trace as $step)
                    {
                        if (!isset($step['class']) || $step['class'] != $class) {
                            continue;
                        }

                        if ($step['function'] == 'getInstance' && $step['args'] == $args) {
                            $useGetter = false;
                            break;
                        }
                    }

                    unset($trace);
                }
            } else {
                $useGetter = false;
            }

            if ($useGetter)
            {
                if (count($args) == 0) {
                    $instance = call_user_func(array($class, 'getInstance'));
                } else {
                    $instance = call_user_func_array(array($class, 'getInstance'), $args);
                }
            } else {
                switch (count($args))
                {
                    case 0: {
                        $instance = new $class;
                    } break;

                    case 1: {
                        // One parameter: common case optimization
                        $instance = new $class($args[0]);
                    } break;

                    case 2: {
                        // Two parameters: common case optimization
                        $instance = new $class($args[0], $args[1]);
                    } break;

                    default: {
                        $reflection = new ReflectionClass($class);
                        $instance   = $reflection->newInstanceArgs($args);
                    } break;
                }
            }

            if (!$save) {
                return $instance;
            }

            self::set($class, $instance, $args);
        }

        return self::$_instances[$key];
    }

    public static function set($class, $instance, $args = array())
    {
        if (!is_object($instance)) {
            throw new AeInstanceException('Invalid value passed: expecting object, ' . AeType::of($instance) . ' given', 400);
        }

        $key = self::generateKey($class, $args);

        self::$_instances[$key] = $instance;

        return true;
    }

    public static function clear($class, $args = array())
    {
        $key = self::generateKey($class, $args);

        if (!isset(self::$_instances[$key])) {
            return false;
        }

        unset(self::$_instances[$key]);

        return true;
    }

    public static function generateKey($class, $args = array())
    {
        $class = (string) $class;

        if ($args instanceof AeArray) {
            $args = $args->getValue();
        }

        if (!is_array($args)) {
            $args = (array) $args;
        }

        foreach ($args as $i => $arg)
        {
            if (is_object($arg)) {
                $args[$i] = spl_object_hash($arg);
            }
        }

        return md5($class.serialize($args));
    }
}

/**
 * Instance exception class
 *
 * Instance-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeInstanceException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Instance');
        parent::__construct($message, $code);
    }
}

?>