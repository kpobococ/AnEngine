<?php

class AeFactory extends AeObject
{
    protected $_instances = array();

    public static function create($class, $args = array(), $save = true, $useGetter = false)
    {
        $factory = self::_getInstance();
        $key     = $factory->_generateKey($class, $args);

        if (!($instance = $factory->_get($key)))
        {
            if (!class_exists($class)) {
                throw new InvalidArgumentException('Class not found: ' . $class, 404);
            }

            if ($useGetter && method_exists($class, 'getInstance'))
            {
                $args     = (array) AeType::unwrap($args);
                $callback = array($class, 'getInstance');

                if (count($args) > 0) {
                    $instance = call_user_func_array($callback, $args);
                } else {
                    $instance = call_user_func($callback);
                }
            } else switch (count($args)) {
                case 0: {
                    $instance = new $class;
                } break;

                case 1: {
                    $instance = new $class($args[0]);
                } break;

                case 2: {
                    $instance = new $class($args[0], $args[1]);
                } break;

                default: {
                    $reflection = new ReflectionClass($class);
                    $instance   = $reflection->newInstanceArgs($args);
                }
            }

            if ($save) {
                $factory->_set($key, $instance);
            }
        }

        return $instance;
    }

    public static function save($class, $instance, $args = array())
    {
        if (!is_object($instance)) {
            throw new InvalidArgumentException('Expecting object, ' . AeType::of($instance) . ' given', 400);
        }

        $factory = self::_getInstance();
        $key     = $factory->_generateKey($class, $args);

        $factory->_set($key, $instance);

        return $instance;
    }

    public static function destroy($class, $args = array())
    {
        $factory = self::_getInstance();
        $key     = $factory->_generateKey($class, $args);

        $factory->_clear($key);

        return true;
    }

    protected static function _getInstance()
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new AeFactory;
        }

        return $instance;
    }

    protected function _get($key)
    {
        return @$this->_instances[$key] || false;
    }

    protected function _set($key, $instance)
    {
        $this->_instances[$key] = $instance;
    }

    protected function _clear($key)
    {
        unset($this->_instances[$key]);
    }

    protected function _generateKey($class, $args = array())
    {
        $class = (string) $class;
        $args  = (array)  AeType::unwrap($args);

        foreach ($args as $i => $arg)
        {
            if (is_object($arg)) {
                $args[$i] = spl_object_hash($arg);
            }
        }

        return md5($class.serialize($args));
    }
}