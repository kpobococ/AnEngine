<?php

class AeNull extends AeType
{
    public static $wrapReturn = null;

    public function setValue($value)
    {
        return $this;
    }

    public function getValue($default = null)
    {
        return $default;
    }

    public function toString()
    {
        return 'null';
    }
}