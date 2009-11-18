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
 * @todo implement set and remove methods to control object cache
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeInstance
{
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
        static $instances = array();

        $key = md5($class.serialize($args));

        if (!isset($instances[$key]))
        {
            if (!class_exists($class)) {
                throw new AeInstanceException(ucfirst($class) . ' class not found', 404);
            }

            if (method_exists($class, 'getInstance'))
            {
                if ($useGetter === null)
                {
                    // Check to prevent an infinite loop
                    $trace     = debug_backtrace();
                    $trace     = array_splice($trace, 1);
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

            $instances[$key] = $instance;
        }

        return $instances[$key];
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