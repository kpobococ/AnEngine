<?php

abstract class AeNumeric extends AeScalar
{
    public static $wrapReturn = null;

    public static function wrap($value)
    {
        if ($value instanceof AeNumeric) {
            return $value;
        }

        if ($value instanceof AeString) {
            $value = (string) $value;
        }

        if (is_numeric($value))
        {
            switch (AeType::of($value))
            {
                case 'integer': {
                    return new AeInteger($value);
                } break;

                case 'float': {
                    return new AeFloat($value);
                } break;

                case 'string':
                {
                    if ($value == (string) (int) $value) {
                        return new AeInteger($value);
                    }

                    return new AeFloat($value);
                } break;
            }
        }

        throw new InvalidArgumentException('Expecting numeric, ' . AeType::of($value) . ' given', 400);
    }
}