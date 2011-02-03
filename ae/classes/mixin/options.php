<?php

class AeMixin_Options extends AeMixin
{
    private $___options = array();

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return $this->_getOwner();
    }

    public function getOptions()
    {
        return $this->___options;
    }

    public function getOption($name, $default = null)
    {
        return $this->_getValue($name, $default);
    }

    public function setOption($name, $value)
    {
        $this->_setValue($name, $value);
        return $this->_getOwner();
    }

    public function clearOption($name)
    {
        $this->_clearValue($name);
        return $this->_getOwner();
    }

    protected function _getValue($name, $default, array &$array = null)
    {
        if ($array === null) {
            $array =& $this->___options;
        }

        if (!strpos($name, '.')) {
            return isset($array[$name]) ? $array[$name] : $default;
        }

        list ($section, $key) = explode('.', $name, 2);

        if (!isset($array[$section]) || !is_array($array[$section])) {
            return $default;
        }

        return $this->_getValue($key, $default, $array[$section]);
    }

    protected function _setValue($name, $value, array &$array = null)
    {
        if ($array === null) {
            $array =& $this->___options;
        }

        if (strpos($name, '.'))
        {
            list ($section, $key) = explode('.', $name, 2);

            if (!isset($array[$section])) {
                $array[$section] = array();
            }

            return $this->_setValue($key, $value, $array[$section]);
        }

        $array[$name] = $value;

        return $this->_getOwner();
    }

    protected function _clearValue($name, array &$array = null)
    {
        if ($array === null) {
            $array =& $this->___options;
        }

        if (!strpos($name, '.'))
        {
            list ($section, $key) = explode('.', $name, 2);

            if (isset($array[$section]) && is_array($array[$section])) {
                return $this->_clearValue($key, $array[$section]);
            }
        } else {
            unset($array[$name]);
        }

        return $this->_getOwner();
    }
}