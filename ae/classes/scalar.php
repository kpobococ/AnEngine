<?php

abstract class AeScalar extends AeType implements Serializable
{
    public static $wrapReturn = null;

    protected $_value;

    public static function wrap($value)
    {
        if ($value instanceof AeScalar) {
            return $value;
        }

        if (is_scalar($value))
        {
            if (is_numeric($value)) {
                return AeNumeric::wrap($value);
            }

            switch (AeType::of($value))
            {
                case 'boolean': {
                    return new AeBoolean($value);
                } break;

                case 'string': {
                    return new AeString($value);
                } break;
            }
        }

        throw new InvalidArgumentException('Expecting scalar, ' . AeType::of($value) . ' given', 400);
    }

    public function __construct($value = null)
    {
        if (!is_null($value)) {
            $this->setValue($value);
        }
    }

    abstract public function setValue($value);

    public function toString()
    {
        if ($this instanceof AeString) {
            return $this;
        }

        return new AeString($this->getValue());
    }

    public function toFloat()
    {
        if ($this instanceof AeFloat) {
            return $this;
        }

        return new AeFloat($this->getValue());
    }

    public function toInteger()
    {
        if ($this instanceof AeInteger) {
            return $this;
        }

        return new AeInteger($this->getValue());
    }

    public function toBoolean()
    {
        if ($this instanceof AeBoolean) {
            return $this;
        }

        return new AeBoolean($this->getValue());
    }

    public function __toString()
    {
        return $this->toString()->getValue('null');
    }

    public function serialize()
    {
        return serialize($this->_value);
    }

    public function unserialize($serialized)
    {
        $this->_value = unserialize($serialized);
        return $this;
    }
}