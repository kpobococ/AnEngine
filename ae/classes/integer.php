<?php

if (!defined('PHP_INT_MIN')) {
    define('PHP_INT_MIN', (int) (PHP_INT_MAX + 1));
}

class AeInteger extends AeNumeric
{
    const MIN = PHP_INT_MIN;
    const MAX = PHP_INT_MAX;

    public function setValue($value)
    {
        $value = AeType::unwrap($value);

        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Expecting integer, ' . AeType::of($value) . ' given', 400);
        }

        if ($value < self::MIN || $value > self::MAX) {
            throw new OutOfRangeException('Value is out of integer range', 400);
        }

        $this->_value = (int) $value;

        return $this;
    }
}