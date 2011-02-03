<?php

abstract class AeObject extends AeObject_Base
{
    private $___mixins = array();

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

        if (($mixin = $this->_getMixinForMethod($setter))) {
            return $mixin->$setter($value);
        }

        $_name  = '_' . $name;

        if ($this->_hasProperty($name)) {
            $this->$name = $value;
        } else if ($this->_hasProperty($_name)) {
            $this->$_name = $value;
        } else if (($mixin = $this->_getMixinForProperty($name))) {
            $mixin->$name = $value;
        } else if (($mixin = $this->_getMixinForProperty($_name))) {
            $mixin->$_name = $value;
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

        if (($mixin = $this->_getMixinForMethod($getter)))
        {
            if (func_num_args() > 1) {
                return $mixin->$getter($default);
            }

            return $mixin->$getter();
        }

        $_name = '_' . $name;
        $value = null;

        if ($this->_hasProperty($name)) {
            $value = $this->$name;
        } else if ($this->_hasProperty($_name)) {
            $value = $this->$_name;
        } else if (($mixin = $this->_getMixinForProperty($name))) {
            $value = $mixin->$name;
        } else if (($mixin = $this->_getMixinForProperty($_name))) {
            $value = $mixin->$_name;
        }

        return is_null($value) ? $default : $value;
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

        if ($this->_getMixinForMethod($getter)) {
            return true;
        }

        if ($this->_hasProperty($name)) {
            return true;
        }

        if ($this->_hasProperty($_name)) {
            return true;
        }

        if ($this->_getMixinForProperty($name)) {
            return true;
        }

        if ($this->_getMixinForProperty($_name)) {
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

        if ($this->_getMixinForMethod($setter)) {
            return true;
        }

        if ($this->_hasProperty($name)) {
            return true;
        }

        if ($this->_hasProperty($_name)) {
            return true;
        }

        if ($this->_getMixinForProperty($name)) {
            return true;
        }

        if ($this->_getMixinForProperty($_name)) {
            return true;
        }

        return false;
    }

    public function hasMethod($method)
    {
        $name = ltrim($method, '_');

        if ($this->_hasMethod($name)) {
            return true;
        } else if ($this->_getMixinForMethod($name)) {
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

            if ($this->_getMixinForProperty($property)) {
                return true;
            }

            if ($this->_getMixinForProperty('_' . $property)) {
                return true;
            }
        }

        return false;
    }

    public function __call($method, $args)
    {
        if (($mixin = $this->_getMixinForMethod($method)))
        {
            if (!empty($args)) {
                return @call_user_func_array(array($mixin, $method), $args);
            }

            return $mixin->$method();
        }

        return parent::__call($method, $args);
    }

    public function __toString()
    {
        if ($this->_hasMethod('toString')) {
            return $this->toString();
        }

        if (($mixin = $this->_getMixinForMethod('toString'))) {
            return $mixin->toString();
        }

        return 'object(' . $this->getClass() . ')';
    }

    public function mixin($mixin)
    {
        if (is_array($mixin) || $mixin instanceof Traversable)
        {
            foreach ($mixin as $v) {
                $this->mixin($v);
            }

            return $this;
        }

        $mixin = (string) $mixin;

        if (!class_exists($mixin)) {
            throw new InvalidArgumentException('Mixin must be a valid class name', 400);
        }

        if (!is_subclass_of($mixin, 'AeMixin')) {
            throw new InvalidArgumentException('Mixin must be a subclass of AeMixin', 400);
        }

        $this->_mixin(new $mixin($this));

        return $this;
    }

    public function hasMixin($mixin)
    {
        $class = get_class($mixin);

        foreach ($this->getMixins() as $_mixin)
        {
            if (get_class($_mixin) == $class) {
                return true;
            }
        }

        return false;
    }

    protected function _getMixinForMethod($name)
    {
        foreach ($this->getMixins() as $mixin)
        {
            if ($mixin->_hasMethod($name)) {
                return $mixin;
            }
        }

        return false;
    }

    protected function _getMixinForProperty($name)
    {
        foreach ($this->getMixins() as $mixin)
        {
            if ($mixin->_hasProperty($name)) {
                return $mixin;
            }
        }

        return false;
    }

    final protected function _mixin(AeMixin $mixin)
    {
        array_unshift($this->___mixins, $mixin);
    }

    final public function getMixins()
    {
        return $this->___mixins;
    }
}