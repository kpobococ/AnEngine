<?php

abstract class AeType extends AeObject
{
    public static $wrapReturn = true;

    public static function of($value)
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_scalar($value) && is_float($value)) {
            return 'float';
        }

        if (is_object($value) && $value instanceof AeType) {
            return self::of($value->getValue());
        }

        return gettype($value);
    }

    public static function wrap($value)
    {
        if ($value instanceof AeType) {
            return $value;
        }

        if (is_null($value)) {
            return new AeNull;
        }

        if (is_scalar($value)) {
            return AeScalar::wrap($value);
        }

        if (is_array($value)) {
            return new AeArray($value);
        }

        return $value;
    }

    public static function unwrap($value)
    {
        if (!($value instanceof AeType)) {
            return $value;
        }

        return $value->getValue();
    }

    public static function wrapReturn($value)
    {
        $type = self::of($value);

        if ($type != 'object')
        {
            $setting = self::$wrapReturn;
            $scalar  = is_scalar($value) || $value instanceof AeScalar;

            if ($scalar && !is_null(AeScalar::$wrapReturn))
            {
                $setting = AeScalar::$wrapReturn;
                $numeric = is_numeric($value) || $value instanceof AeNumeric;

                if ($numeric && !is_null(AeNumeric::$wrapReturn)) {
                    $setting = AeNumeric::$wrapReturn;
                }
            } else if ($type == 'null' && !is_null(AeNull::$wrapReturn)) {
                $setting = AeNull::$wrapReturn;
            } else if ($type == 'array' && !is_null(AeArray::$wrapReturn)) {
                $setting = AeArray::$wrapReturn;
            }

            if ($setting === true) {
                return self::wrap($value);
            } else if ($setting === false) {
                return self::unwrap($value);
            }

            return $value;
        }

        return $value;
    }
}