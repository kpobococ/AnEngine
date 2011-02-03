<?php

class AeNode extends AeObject implements Serializable
{
    public function __construct($properties = null)
    {
        $this->mixin('AeMixin_Properties');

        if (!is_null($properties)) {
            $this->set($properties);
        }
    }

    public function get($name, $default = null)
    {
        $name = AeType::unwrap($name);

        if (is_array($name) || $this->isGettable($name)) {
            return parent::get($name, $default);
        }

        return $this->getProperty($name, $default);
    }

    public function set($name, $value = null)
    {
        $name = AeType::unwrap($name);

        if (is_array($name) || $this->isSettable($name)) {
            return parent::set($name, $value);
        }

        return $this->setProperty($name, $value);
    }

    public function clear($name)
    {
        $name = AeType::unwrap($name);

        if (is_array($name) || $this->isSettable($name)) {
            return parent::clear($name);
        }

        return $this->clearProperty($name);
    }

    public function __isset($name)
    {
        if ($this->propertyExists($name)) {
            return parent::__isset($name);
        }

        return $this->getProperty($name, null) !== null;
    }

    public function serialize()
    {
        return serialize($this->properties);
    }

    public function unserialize($properties)
    {
        $this->__construct(unserialize($properties));
        return $this;
    }
}