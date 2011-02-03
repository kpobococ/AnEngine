<?php

class AeArray_Iterator extends AeObject implements Iterator
{
    protected $_array;
    protected $_keys;

    public function __construct(AeArray $array)
    {
        $this->_array = $array;
        $this->_keys  = AeType::unwrap($array->getKeys());
    }

    public function current()
    {
        return $this->valid() ? $this->_array[current($this->_keys)] : null;
    }

    public function key()
    {
        return current($this->_keys);
    }

    public function next()
    {
        next($this->_keys);

        return $this->current();
    }

    public function rewind()
    {
        reset($this->_keys);

        return $this->current();
    }

    public function valid()
    {
        $key = $this->key();
        return $key !== null && $key !== false;
    }
}