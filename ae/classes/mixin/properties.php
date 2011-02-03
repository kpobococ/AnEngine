<?php

class AeMixin_Properties extends AeMixin
{
    private $___properties = array();

    public function getProperty($name, $default = null)
    {
        return isset($this->___properties[$name]) ? $this->___properties[$name] : $default;
    }

    public function setProperty($name, $value)
    {
        $this->___properties[$name] = $value;

        return $this->_getOwner();
    }

    public function clearProperty($name)
    {
        unset($this->___properties[$name]);

        return $this->_getOwner();
    }

    public function getProperties()
    {
        return $this->___properties;
    }

    public function setProperties($properties)
    {
        $this->___properties = $properties;

        return $this->_getOwner();
    }
}