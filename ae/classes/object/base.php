<?php

abstract class AeObject_Base
{
    public function set($property, $value)
    {
        if (is_array($property) || $property instanceof Traversable)
        {
            foreach ($property as $k => $v) {
                $this->set($k, $v);
            }

            return $this;
        }

        if (!is_string($property) || is_numeric($property)) {
            throw new InvalidArgumentException('Property name has to be a string', 400);
        }

        $name   = ltrim($property, '_');
        $setter = 'set' . ucfirst($name);

        if ($this->_hasMethod($setter)) {
            return $this->$setter($value);
        }

        $_name  = '_' . $name;

        if ($this->_hasProperty($name)) {
            $this->$name = $value;
        } else if ($this->_hasProperty($_name)) {
            $this->$_name = $value;
        } else {
            throw new OutOfBoundsException('Property does not exist: ' . $name, 404);
        }

        return $this;
    }

    public function get($property, $default = null)
    {
        if (is_array($property) || $property instanceof Traversable)
        {
            $return = array();

            foreach ($property as $k => $d) {
                $return[$k] = $this->get($k, $d);
            }

            return $return;
        }

        if (!is_string($property) || is_numeric($property)) {
            throw new InvalidArgumentException('Property name has to be a string', 400);
        }

        $name   = ltrim($property, '_');
        $getter = 'get' . ucfirst($name);

        if ($this->_hasMethod($getter))
        {
            if (func_num_args() > 1) {
                return $this->$getter($default);
            }

            return $this->$getter();
        }

        $_name = '_' . $name;
        $value = null;

        if ($this->_hasProperty($name)) {
            $value = $this->$name;
        } else if ($this->_hasProperty($_name)) {
            $value = $this->$_name;
        }

        return is_null($value) ? $default : $value;
    }

    public function clear($property)
    {
        if (is_array($property) || $property instanceof Traversable)
        {
            foreach ($property as $k) {
                $this->clear($k);
            }

            return $this;
        }

        if (!is_string($property) || is_numeric($property)) {
            throw new InvalidArgumentException('Property name has to be a string', 400);
        }

        if ($this->isSettable($property)) {
            $this->set($property, null);
        }

        return $this;
    }

    public function hasProperty($name)
    {
        return $this->isGettable($name) && $this->isSettable($name);
    }

    public function isGettable($property)
    {
        if (!is_string($property) || is_numeric($property)) {
            throw new InvalidArgumentException('Property name has to be a string', 400);
        }

        $name   = ltrim($property, '_');
        $getter = 'get' . ucfirst($name);
        $_name  = '_' . $name;

        if ($this->_hasMethod($getter)) {
            return true;
        }

        if ($this->_hasProperty($name)) {
            return true;
        }

        if ($this->_hasProperty($_name)) {
            return true;
        }

        return false;
    }

    public function isSettable($property)
    {
        if (!is_string($property) || is_numeric($property)) {
            throw new InvalidArgumentException('Property name has to be a string', 400);
        }

        $name   = ltrim($property, '_');
        $setter = 'set' . ucfirst($name);
        $_name  = '_' . $name;

        if ($this->_hasMethod($setter)) {
            return true;
        }

        if ($this->_hasProperty($name)) {
            return true;
        }

        if ($this->_hasProperty($_name)) {
            return true;
        }

        return false;
    }

    public function getClass()
    {
        return get_class($this);
    }

    public function hasMethod($method)
    {
        $name = ltrim($method, '_');

        if ($this->_hasMethod($name)) {
            return true;
        }

        $prefix = substr($name, 0, 3);

        if ($prefix == 'get' || $prefix == 'set')
        {
            $property = ltrim(substr($name, 3), '_');
            $property = strtolower($property[0]) . substr($property, 1);

            if ($this->_hasProperty($property)) {
                return true;
            }

            if ($this->_hasProperty('_' . $property)) {
                return true;
            }
        }

        return false;
    }

    public function call($method, $args = null)
    {
        $name = ltrim($method, '_');

        if (func_num_args() > 1)
        {
            if ($args instanceof AeArray) {
                $args = $args->getValue();
            } else if ($args instanceof ArrayObject) {
                $args = $args->getArrayCopy();
            } else if ($args instanceof Traversable) {
                $a = array();

                foreach ($args as $v) {
                    $a[] = $v;
                }

                $args = $a;
            } else {
                $args = (array) $args;
            }
        }

        if ($this->_hasMethod($method))
        {
            if (is_array($args)) {
                return call_user_func_array(array($this, $method), $args);
            }

            return $this->$method();
        }

        return $this->__call($method, is_array($args) ? $args : array());
    }

    public function __call($method, $args)
    {
        $prefix = substr($method, 0, 3);

        if ($prefix == 'get' || $prefix == 'set')
        {
            $property = ltrim(substr($method, 3), '_');
            $property = strtolower($property[0]) . substr($property, 1);

            if ($prefix == 'get' && $this->isGettable($property))
            {
                if (isset($args[0])) {
                    return $this->get($property, $args[0]);
                }

                return $this->get($property);
            } else if ($prefix == 'set' && $this->isSettable($property)) {
                return $this->set($property, @$args[0]);
            }
        }

        throw new BadMethodCallException('Call to undefined method ' . $this->getClass() . '::' . $method . '()', 404);
    }

    public function __get($property)
    {
        return $this->get($property);
    }

    public function __set($property, $value)
    {
        return $this->set($property, $value);
    }

    public function __isset($property)
    {
        return !is_null($this->get($property, null));
    }

    public function __unset($property)
    {
        $this->clear($property);
    }

    public function __toString()
    {
        if ($this->_hasMethod('toString')) {
            return $this->toString();
        }

        return 'object(' . $this->getClass() . ')';
    }

    protected function _hasProperty($name)
    {
        return property_exists($this, $name);
    }

    protected function _hasMethod($name)
    {
        return method_exists($this, $name);
    }
}