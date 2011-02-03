<?php

class AeBoolean extends AeScalar
{
    public function setValue($value)
    {
        $value = AeType::unwrap($value);

        if (is_string($value) && $value == 'false') {
            $value = false;
        }

        $value = (bool) $value;

        if (!is_bool($value)) {
            throw new InvalidArgumentException('Expecting boolean, ' . AeType::of($value), 400);
        }

        $this->_value = $value;

        return $this;
    }
}