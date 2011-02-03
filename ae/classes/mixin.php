<?php

abstract class AeMixin extends AeObject_Base
{
    private $___owner;

    public function __construct(AeObject $owner)
    {
        $this->___owner = $owner;
    }

    public function set($property, $value)
    {
        return $this->_getOwner()->set($property, $value);
    }

    public function get($property, $default = null)
    {
        return $this->_getOwner()->get($property, $value);
    }

    public function isGettable($property)
    {
        return $this->_getOwner()->isGettable($property);
    }

    public function isSettable($property)
    {
        return $this->_getOwner()->isSettable($property);
    }

    public function getClass()
    {
        return $this->_getOwner()->getClass();
    }

    public function hasMethod($method)
    {
        return $this->_getOwner()->hasMethod($method);
    }

    public function __call($method, $args)
    {
        return $this->_getOwner()->__call($method, $args);
    }

    public function __toString()
    {
        return $this->_getOwner()->__toString();
    }

    final protected function _getOwner()
    {
        return $this->___owner;
    }
}