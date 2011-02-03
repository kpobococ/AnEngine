<?php

class AeFloat extends AeNumeric
{
    public function setValue($value)
    {
        $value = AeType::unwrap($value);

        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Expecting float, ' . AeType::of($value) . ' given', 400);
        }

        $this->_value = (float) $value;

        return $this;
    }

    public function round($precision = 0)
    {
        if (!is_scalar($precision) && !($precision instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting integer, ' . AeType::of($value) . ' given', 400);
        }

        return new AeFloat(round($this->_value, (int)(string)$precision));
    }

    public function floor()
    {
        return new AeFloat(floor($this->_value));
    }

    public function ceil()
    {
        return new AeFloat(ceil($this->_value));
    }

    public function format($decimals = 0, $point = '.', $separator = ',')
    {
        if (!is_scalar($decimals) && !($decimals instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting decimals to be integer, ' . AeType::of($decimals) . ' given', 400);
        }

        if (!is_scalar($point) && !($point instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting point to be string, ' . AeType::of($point) . ' given', 400);
        }

        if (!is_scalar($separator) && !($separator instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting separator to be string, ' . AeType::of($separator) . ' given', 400);
        }

        // *** Support multibyte characters in point and separator chars
        $point     = iconv_substr((string)$point, 0, 1, 'UTF-8');
        $separator = iconv_substr((string)$separator, 0, 1, 'UTF-8');

        if (strlen($point) > 1 || strlen($separator) > 1) {
            $string = number_format($this->_value, (int)(string)$decimals, "\r", "\t");
            $string = str_replace(array("\r", "\t"), array($point, $separator), $string);
        } else {
            $string = number_format($this->_value, (int)(string)$decimals, $point, $separator);
        }

        return new AeString($string);
    }
}