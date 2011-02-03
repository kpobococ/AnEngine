<?php

class AeOptions extends AeObject implements Serializable
{
    public function __construct($options = null)
    {
        $this->mixin('AeMixin_Options');

        if (!is_null($options)) {
            $this->setOptions($options);
        }
    }

    public function get($name, $default = null)
    {
        $name = AeType::unwrap($name);

        if (is_array($name) || $this->isGettable($name)) {
            return parent::get($name, $default);
        }

        return $this->getOption($name, $default);
    }

    public function set($name, $value)
    {
        $name = AeType::unwrap($name);

        if (is_array($name) || $this->isSettable($name)) {
            return parent::set($name, $value);
        }

        return $this->setOption($name, $value);
    }

    public function clear($name)
    {
        $name = AeType::unwrap($name);

        if (is_array($name) || $this->isSettable($name)) {
            return parent::clear($name);
        }

        return $this->clearOption($name);
    }

    public function __isset($name)
    {
        if ($this->propertyExists($name)) {
            return parent::__isset($name);
        }

        return $this->getOption($name, null) !== null;
    }

    public function serialize()
    {
        return serialize($this->options);
    }

    public function unserialize($options)
    {
        $this->__construct(unserialize($options));
        return $this;
    }
}