<?php

class AeString extends AeScalar implements ArrayAccess
{
    const INDEX_LEFT  = 1;
    const INDEX_RIGHT = 2;

    const TRIM_LEFT  = 1;
    const TRIM_RIGHT = 2;
    const TRIM_BOTH  = 3;

    const PAD_LEFT  = STR_PAD_LEFT;
    const PAD_RIGHT = STR_PAD_RIGHT;
    const PAD_BOTH  = STR_PAD_BOTH;

    public function setValue($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } else {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('Expecting string, ' . AeType::of($value) . ' given', 400);
        }

        $this->_value = $value;

        return $this;
    }

    public function capitalize()
    {
        if (function_exists('mb_strtoupper')) {
            return new AeString(mb_strtoupper((string)$this->slice(0, 1), 'UTF-8') . $this->slice(1));
        }

        return new AeString(ucfirst($this->_value));
    }

    public function charAt($offset)
    {
        if (!is_scalar($offset) && !($offset instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting offset to be integer, ' . AeType::of($offset) . ' given', 400);
        }

        $offset = (int)(string)$offset;

        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException('Offset value outside string bounds', 416);
        }

        return new AeString(iconv_substr($this->_value, $offset, 1, 'UTF-8'));
    }

    public function concat()
    {
        $args = func_get_args();
        return new AeString($this->_value . implode('', $args));
    }

    public function format()
    {
        $args = func_get_args();

        foreach ($args as $i => $arg)
        {
            if ($arg instanceof AeType) {
                $args[$i] = $arg->getValue();
            }
        }

        array_unshift($args, $this->_value);

        return new AeString(call_user_func_array('sprintf', $args));
    }

    public function indexOf($needle, $offset = 0, $case_sensitive = true, $mode = AeString::INDEX_LEFT)
    {
        if (!is_scalar($needle) && !($needle instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting needle to be string, ' . AeType::of($needle) . ' given', 400);
        }

        $needle = (string) $needle;

        if (!is_scalar($offset) && !($offset instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting offset to be integer, ' . AeType::of($offset) . ' given', 400);
        }

        $offset = (int)(string)$offset;

        if (!is_scalar($case_sensitive) && !($case_sensitive instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting case_sensitive to be boolean, ' . AeType::of($case_sensitive) . ' given', 400);
        }

        $case_sensitive = (bool)(string)$case_sensitive;

        if ($mode == AeString::INDEX_RIGHT)
        {
            if ($case_sensitive === false)
            {
                if (function_exists('mb_strripos')) {
                    return mb_strripos($this->_value, $needle, $offset, 'UTF-8');
                }
                // TODO: provide alternative?

                return strripos($this->_value, $needle, $offset);
            }

            if ($offset !== 0)
            {
                if (function_exists('mb_strrpos')) {
                    // *** Use mb_string function instead
                    return mb_strrpos($this->_value, $needle, $offset, 'UTF-8');
                }

                // *** Emulate $offset parameter for iconv_strrpos
                if ($offset > 0) {
                    $string = iconv_substr($this->_value, $offset, $this->length, 'UTF-8');
                } else {
                    $string = iconv_substr($this->_value, 0, $offset, 'UTF-8');
                    $offset = 0;
                }

                return iconv_strrpos($string, $needle, 'UTF-8') + $offset;
            }

            return iconv_strrpos($this->_value, $needle, 'UTF-8');
        }

        if ($case_sensitive === false)
        {
            if (function_exists('mb_stripos')) {
                return mb_stripos($this->_value, $needle, $offset, 'UTF-8');
            }
            // TODO: provide alternative?

            return stripos($this->_value, $needle, $offset);
        }

        return iconv_strpos($this->_value, $needle, $offset, 'UTF-8');
    }

    public function lastIndexOf($needle, $offset = 0, $case_sensitive = true)
    {
        return $this->indexOf($needle, $offset, $case_sensitive, AeString::INDEX_RIGHT);
    }

    public function pad($length, $string = ' ', $mode = AeString::PAD_BOTH)
    {
        if (!is_scalar($length) && !($length instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting length to be integer, ' . AeType::of($length) . ' given', 400);
        }

        $length = (int)(string)$length;

        if ($length <= 0) {
            throw new InvalidArgumentException('Length must be greater than zero', 400);
        }

        if ($length <= $this->length) {
            return clone $this;
        }

        if (!in_array($mode, array(self::PAD_LEFT, self::PAD_RIGHT, self::PAD_BOTH))) {
            throw new InvalidArgumentException('Expecting mode to be one of AeString::PAD constants', 400);
        }

        $string = (string) $string;

        // *** Use of regular strlen here is intentional
        if (strlen($string) == 0) {
            $string = ' ';
        }

        if (strlen($this->_value) == $this->length && strlen($string) == iconv_strlen($string, 'UTF-8')) {
            return new AeString(str_pad($this->_value, $length, $string, $mode));
        }

        // *** Multibyte str_pad
        $_tlen = $length - $this->length;
        $_llen = 0; // Left prefix length
        $_rlen = 0; // Right prefix length
        $_slen = 0; // Repeat string length
        $_mlen = $_tlen; // Max prefix length
        $_sstr = ''; // Repeat string
        $_lstr = ''; // Left prefix
        $_rstr = ''; // Right prefix

        switch ($mode)
        {
            case AeString::PAD_LEFT: {
                $_llen = $_tlen;
            } break;

            case AeString::PAD_RIGHT: {
                $_rlen = $_tlen;
            } break;

            case AeString::PAD_BOTH: {
                // *** Original str_pad favors right over left
                $_llen = floor($_tlen / 2);
                $_rlen = ceil($_tlen / 2);
                $_mlen = $_rlen > $_llen ? $_rlen : $_llen;
            } break;
        }

        do {
            $_sstr .= $string;
        } while (($_slen = iconv_strlen($_sstr, 'UTF-8')) < $_mlen);

        if ($_llen > 0) {
            $_lstr = $_slen > $_llen ? iconv_substr($_sstr, 0, $_llen, 'UTF-8') : $_sstr;
        }

        if ($_rlen > 0) {
            $_rstr = $_slen > $_rlen ? iconv_substr($_sstr, 0, $_rlen, 'UTF-8') : $_sstr;
        }

        return new AeString($_lstr . $this->_value . $_rstr);
    }

    public function padLeft($length, $string = ' ')
    {
        return $this->pad($length, $string, AeString::PAD_LEFT);
    }

    public function padRight($length, $string = ' ')
    {
        return $this->pad($length, $string, AeString::PAD_RIGHT);
    }

    public function parse($format)
    {
        if (!is_scalar($format) && !($format instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting format to be string, ' . AeType::of($format) . ' given', 400);
        }

        return new AeArray(sscanf($this->_value, (string) $format));
    }

    /**
     * @deprecated use AeString::slice() instead
     */
    public function part($start, $length = null)
    {
        return $this->slice($start, $length);
    }

    public function printf()
    {
        $args = func_get_args();
        return $this->call('format', $args);
    }

    public function repeat($count)
    {
        if (!is_scalar($count) && !($count instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting count to be integer, ' . AeType::of($count) . ' given', 400);
        }

        $count = (int)(string)$count;

        if ($count <= 0) {
            throw new InvalidArgumentException('Count must be greater than zero', 400);
        }

        return new AeString(str_repeat($this->_value, $count));
    }

    public function replace($search, $replace)
    {
        if ($search instanceof AeType) {
            $search = $search->getValue();
        }

        if ($replace instanceof AeType) {
            $replace = $replace->getValue();
        }

        return new AeString(str_replace($search, $replace, $this->_value));
    }

    public function reverse()
    {
        $length = $this->length;
        $strlen = strlen($this->_value);

        if ($length != $strlen)
        {
            // *** Multibyte-safe reverse
            $string = '';

            for ($i = $length - 1; $i >= 0; $i--) {
                $string .= $this->charAt($i);
            }

            return new AeString($string);
        }

        return new AeString(strrev($this->_value));
    }

    public function scanf($format)
    {
        return $this->parse($format);
    }

    public function slice($start, $length = null)
    {
        if (!is_scalar($start) && !($start instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting start to be integer, ' . AeType::of($start) . ' given', 400);
        }

        $start = (int)(string)$start;

        if (!$this->offsetExists($start)) {
            throw new OutOfBoundsException('Start value outside string bounds', 416);
        }

        if ($length !== null)
        {
            if (!is_scalar($length) && !($length instanceof AeScalar)) {
                throw new InvalidArgumentException('Expecting length to be integer, ' . AeType::of($length) . ' given', 400);
            }

            $length = (int)(string)$length;

            return new AeString(iconv_substr($this->_value, $start, $length, 'UTF-8'));
        }

        return new AeString(iconv_substr($this->_value, $start, $this->length, 'UTF-8'));
    }

    public function split($separator, $limit = null)
    {
        if (!is_scalar($separator) && !($separator instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting separator to be string, ' . AeType::of($separator) . ' given', 400);
        }

        $separator = (string) $separator;

        if ($separator === '') {
            throw new InvalidArgumentException('Separator cannot be empty', 400);
        }

        if ($limit !== null)
        {
            if (!is_scalar($limit) && !($limit instanceof AeScalar)) {
                throw new InvalidArgumentException('Expecting limit to be integer, ' . AeType::of($limit) . ' given', 400);
            }

            $limit = (int)(string)$limit;

            return new AeArray(explode($separator, $this->_value, $limit));
        }

        return new AeArray(explode($separator, $this->_value));
    }

    /**
     * @deprecated use AeString::slice() instead
     */
    public function substring($start, $length = null)
    {
        return $this->slice($start, $length);
    }

    public function toCamelCase($delimiters = '-_ ')
    {
        if (!is_scalar($delimiters) && !($delimiters instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting delimiters to be string, ' . AeType::of($delimiters) . ' given', 400);
        }

        $delimiters = (string) $delimiters;
        $delims     = array();

        for ($i = 0, $l = iconv_strlen($delimiters, 'UTF-8'); $i < $l; $i++)
        {
            $delim = iconv_substr($arg, $i, 1, 'UTF-8');

            if (!in_array($delim, $delims)) {
                $delims[] = $delim;
            }
        }

        $delims = preg_quote(implode('', $delims));
        $string = preg_replace('#[' . $delims . ']+#u', ' ', $this->_value);
        $bits   = explode(' ', $string);

        if (function_exists('mb_strtoupper'))
        {
            foreach ($bits as $i => $bit)
            {
                $length = iconv_strlen($bit, 'UTF-8');

                if ($length > 0) {
                    $start    = iconv_substr($bit, 0, 1, 'UTF-8');
                    $end      = iconv_substr($bit, 1, $length, 'UTF-8');
                    $bits[$i] = mb_strtoupper($start, 'UTF-8') . $end;
                }
            }

            return new AeString(implode('', $bits));
        }

        array_walk($bits, 'ucfirst');

        return new AeString(implode('', $bits));
    }

    public function toLowerCase()
    {
        if (function_exists('mb_strtolower')) {
            return new AeString(mb_strtolower($this->_value, 'UTF-8'));
        }

        return new AeString(strtolower($this->_value));
    }

    public function toUpperCase()
    {
        if (function_exists('mb_strtoupper')) {
            return new AeString(mb_strtoupper($this->_value, 'UTF-8'));
        }

        return new AeString(strtoupper($this->_value));
    }

    public function trim($mode = AeString::TRIM_BOTH)
    {
        // TODO: add chars parameter with multibyte support
        // TODO: refactor trimLeft and trimRight after chars param is added

        switch ($mode)
        {
            case AeString::TRIM_LEFT: {
                return new AeString(ltrim($this->_value));
            } break;

            case AeString::TRIM_RIGHT: {
                return new AeString(rtrim($this->_value));
            } break;

            case AeString::TRIM_BOTH:
            default: {
                return new AeString(trim($this->_value));
            } break;
        }

        throw new InvalidArgumentException('Expecting mode to be one of AeString::TRIM constants', 400);
    }

    public function trimLeft()
    {
        return $this->trim(AeString::TRIM_LEFT);
    }

    public function trimRight()
    {
        return $this->trim(AeString::TRIM_RIGHT);
    }

    public function getLength()
    {
        return iconv_strlen($this->_value, 'UTF-8');
    }

    public function offsetExists($offset)
    {
        if (!is_scalar($offset) && !($offset instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting offset to be integer, ' . AeType::of($offset) . ' given', 400);
        }

        $offset = (int)(string)$offset;

        if ($offset < 0) {
            return false;
        }

        return $this->length > $offset;
    }

    public function offsetGet($offset)
    {
        return $this->charAt($offset);
    }

    public function offsetSet($offset, $value)
    {
        if (!is_scalar($offset) && !($offset instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting offset to be integer, ' . AeType::of($offset) . ' given', 400);
        }

        $offset = (int)(string)$offset;

        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException('Offset value outside string bounds', 416);
        }

        if (!is_scalar($value) && !($value instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting value to be string, ' . AeType::of($value) . ' given', 400);
        }

        $value = (string) $value;

        if (iconv_strlen($value, 'UTF-8') > 1) {
            $value = iconv_substr($value, 0, 1, 'UTF-8');
        }

        $string = $this->slice(0, $offset)->getValue()
                . $value
                . $this->slice($offset + 1)->getValue();

        $this->setValue($string);

        return $this;
    }

    public function offsetUnset($offset)
    {
        if (!is_scalar($offset) && !($offset instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting offset to be integer, ' . AeType::of($offset) . ' given', 400);
        }

        $offset = (int)(string)$offset;

        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException('Offset value outside string bounds', 416);
        }

        $this->setValue($this->slice(0, $offset)->getValue() . $this->slice($offset + 1)->getValue());

        return $this;
    }
}