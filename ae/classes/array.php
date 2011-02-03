<?php

class AeArray extends AeType implements ArrayAccess, Countable, Serializable, IteratorAggregate
{
    const INDEX_LEFT  = 1;
    const INDEX_RIGHT = 2;

    const SORT_FLAG_CI = 1;
    const SORT_FLAG_KEY = 2;
    const SORT_FLAG_ASSOC = 4;

    const SORT_REGULAR = 1;
    const SORT_NUMERIC = 2;
    const SORT_STRING  = 3;
    const SORT_LOCALE  = 4;
    const SORT_NATURAL = 5;

    public static $wrapReturn = null;

    protected $_value;

    public static function range($start, $end, $step = 1)
    {
        $range = @range(AeType::unwrap($start), AeType::unwrap($end), AeType::unwrap($step));

        if (!$range) {
            $error = error_get_last();

            throw new RuntimeException($error['message'], 400);
        }

        return new AeArray($range);
    }

    public function __construct()
    {
        $count = func_num_args();

        switch ($count)
        {
            case 0: {
                $value = null;
            } break;

            case 1: {
                $value = func_get_arg(0);

                if ($value instanceof AeArray) {
                    $value = $value->getValue();
                }

                if (!is_array($value)) {
                    $value = array($value);
                }
            } break;

            default: {
                $value = func_get_args();
            } break;
        }

        if (!is_null($value)) {
            $this->setValue($value);
        }
    }

    public function setValue($value)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Expecting array, ' . AeType::of($value) . ' given', 400);
        }

        $this->_value = $this->_safeValue($value);

        return $this;
    }

    protected function _safeValue($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $_value = array();

        foreach ($value as $k => $v) {
            $_value[$k] = $this->_safeValue($v);
        }

        return $_value;
    }

    public function getValue($default = null)
    {
        if ($this->_value === null) {
            return $default;
        }

        $value = $this->_safeValue($this->_value);

        // *** Clear wrapper classes
        foreach ($value as $k => $v) {
            $value[$k] = AeType::unwrap($v);
        }

        return $value;
    }

    public function getLast()
    {
        if ($this->length == 0) {
            return AeType::wrapReturn(null);
        }

        return AeType::wrapReturn(end($this->_value));
    }

    public function getFirst()
    {
        if ($this->length == 0) {
            return AeType::wrapReturn(null);
        }

        return AeType::wrapReturn(reset($this->_value));
    }

    public function getKeys()
    {
        return new AeArray(array_keys($this->_value));
    }

    public function getValues()
    {
        return new AeArray(array_values($this->_value));
    }

    public function getLength()
    {
        return count($this->_value);
    }

    public function __toString()
    {
        return $this->getClass() . '(' . $this->length . ')';
    }

    public function associate($keys)
    {
        if (empty($this->_value) || empty($keys)) {
            return new AeArray(array());
        }

        return new AeArray(array_combine($keys, $this->_value));
    }

    public function clean($preserve_keys = false)
    {
        if (!is_scalar($preserve_keys) && !($preserve_keys instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting preserve_keys to be boolean, ' . AeType::of($preserve_keys) . ' given', 400);
        }

        $preserve_keys = (bool)(string)$preserve_keys;

        $return = array();

        foreach ($this->_value as $k => $v)
        {
            if (AeType::of($v) !== 'null') {
                $return[$preserve_keys ? $k : null] = $v;
            }
        }

        return new AeArray($return);
    }

    public function combine($values)
    {
        if (empty($this->_value) || empty($values)) {
            return new AeArray(array());
        }

        return new AeArray(array_combine($this->_value, $values));
    }

    public function concat()
    {
        $args = func_get_args();

        foreach ($args as $i => $arg) {
            $args[$i] = (array) AeType::unwrap($arg);
        }

        array_unshift($args, $this->_value);

        return new AeArray(call_user_func_array('array_merge', $args));
    }

    public function contains($needle, $offset = 0)
    {
        return $this->indexOf($needle, $offset, true);
    }

    public function every($callback)
    {
        if (!is_callable($callback) && !($callback instanceof AeCallback)) {
            throw new InvalidArgumentException('Expecting callback to be callable, ' . AeType::of($callback) . ' given', 400);
        }

        if (!is_callable($callback) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        foreach ($this->_value as $k => $v)
        {
            if (!call_user_func($callback, $v, $k)) {
                return false;
            }
        }

        return true;
    }

    public function filter($callback, $preserve_keys = false)
    {
        if (!is_callable($callback) && !($callback instanceof AeCallback)) {
            throw new InvalidArgumentException('Expecting callback to be callable, ' . AeType::of($callback) . ' given', 400);
        }

        if (!is_callable($callback) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }
        
        if (!is_scalar($preserve_keys) && !($preserve_keys instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting preserve_keys to be boolean, ' . AeType::of($preserve_keys) . ' given', 400);
        }

        $preserve_keys = (bool)(string)$preserve_keys;

        $return = array();

        foreach ($this->_value as $k => $v)
        {
            if (call_user_func($callback, $v, $k)) {
                $return[$preserve_keys ? $k : null] = $v;
            }
        }

        return new AeArray($return);
    }

    public function flip()
    {
        return new AeArray(array_flip($this->_value));
    }

    public function indexOf($needle, $offset = 0, $strict = false, $mode = AeArray::INDEX_LEFT)
    {
        if (!is_scalar($offset) && !($offset instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting offset to be integer, ' . AeType::of($offset) . ' given', 400);
        }

        $offset = (int)(string)$offset;

        if (!is_scalar($strict) && !($strict instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting strict to be boolean, ' . AeType::of($strict) . ' given', 400);
        }

        $strict = (bool)(string)$strict;

        if ($offset > 0) {
            $values = array_slice($this->_value, $offset, $this->length, true);
        } else if ($offset < 0) {
            $values = array_slice($this->_value, 0, $offset, true);
        } else {
            $values = $this->_value;
        }

        if ($mode == AeArray::INDEX_RIGHT) {
            $values = array_reverse($values, true);
        }

        return array_search($needle, $values, $strict);
    }

    public function join($glue = '')
    {
        if (!is_scalar($glue) && !($glue instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting glue to be string, ' . AeType::of($glue) . ' given', 400);
        }

        return new AeString(implode((string) $glue, $this->_value));
    }

    public function lastIndexOf($needle, $offset = 0, $strict = false)
    {
        return $this->indexOf($needle, $offset, $strict, AeArray::INDEX_RIGHT);
    }

    public function map($callback, $preserve_keys = false)
    {
        if (!is_callable($callback) && !($callback instanceof AeCallback)) {
            throw new InvalidArgumentException('Expecting callback to be callable, ' . AeType::of($callback) . ' given', 400);
        }

        if (!is_callable($callback) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        if (!is_scalar($preserve_keys) && !($preserve_keys instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting preserve_keys to be boolean, ' . AeType::of($preserve_keys) . ' given', 400);
        }

        $preserve_keys = (bool)(string)$preserve_keys;

        $return = array();

        foreach ($this->_value as $k => $v) {
            $return[$preserve_keys ? $k : null] = call_user_func($callback, $v, $k);
        }

        return new AeArray($return);
    }

    public function pop()
    {
        $return = array_pop($this->_value);

        return AeType::wrapReturn($return);
    }

    public function push($value)
    {
        $args = func_get_args();

        foreach ($args as $arg) {
            array_push($this->_value, AeType::unwrap($arg));
        }

        return $this;
    }

    public function random()
    {
        $k = array_rand($this->_value);

        return $this[$k];
    }

    public function reverse($preserve_keys = false)
    {
        if (!is_scalar($preserve_keys) && !($preserve_keys instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting preserve_keys to be boolean, ' . AeType::of($preserve_keys) . ' given', 400);
        }

        $preserve_keys = (bool)(string)$preserve_keys;

        return new AeArray(array_reverse($this->_value, $preserve_keys));
    }

    public function shift()
    {
        $return = array_shift($this->_value);

        return AeType::wrapReturn($return);
    }

    public function shuffle($preserve_keys = false)
    {
        if (!is_scalar($preserve_keys) && !($preserve_keys instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting preserve_keys to be boolean, ' . AeType::of($preserve_keys) . ' given', 400);
        }

        $preserve_keys = (bool)(string)$preserve_keys;

        if ($preserve_keys)
        {
            $values = array_keys($this->_value);

            shuffle($values);

            $return = array();

            foreach ($values as $k) {
                $return[$k] = $this->_value[$k];
            }

            $this->_value = $return;
        } else {
            shuffle($this->_value);
        }

        return $this;
    }

    public function slice($start, $length = null)
    {
        if (!is_scalar($start) && !($start instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting start to be integer, ' . AeType::of($start) . ' given', 400);
        }

        $start = (int)(string)$start;

        if ($start >= $this->length) {
            throw new OutOfBoundsException('Start value outside array bounds', 416);
        }

        if ($length !== null)
        {
            if (!is_scalar($length) && !($length instanceof AeScalar)) {
                throw new InvalidArgumentException('Expecting length to be integer, ' . AeType::of($length) . ' given', 400);
            }

            $length = (int)(string)$length;
        }

        if ($length !== null) {
            return new AeArray(array_slice($this->_value, $start, $length));
        }

        return new AeArray(array_slice($this->_value, $start));
    }

    public function some($callback)
    {
        if (!is_callable($callback) && !($callback instanceof AeCallback)) {
            throw new InvalidArgumentException('Expecting callback to be callable, ' . AeType::of($callback) . ' given', 400);
        }

        if (!is_callable($callback) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        foreach ($this->_value as $k => $v)
        {
            if (call_user_func($callback, $v, $k)) {
                return true;
            }
        }

        return false;
    }

    public function sort($mode = AeArray::SORT_REGULAR, $flags = 0)
    {
        $modes = array(
            AeArray::SORT_REGULAR,
            AeArray::SORT_NUMERIC,
            AeArray::SORT_STRING,
            AeArray::SORT_LOCALE,
            AeArray::SORT_NATURAL
        );

        if (!is_callable($mode) && !($mode instanceof AeCallback)
          && ((!is_scalar($mode) && !($mode instanceof AeScalar)) || !in_array($mode, $modes))) {
            throw new InvalidArgumentException('Expecting mode to be callback or one of AeArray::SORT constants, ' . AeType::of($mode) . ' given', 400);
        }

        if (!is_scalar($flags) && !($flags instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting flags to be integer, ' . AeType::of($flags) . ' given', 400);
        }

        $flags = (int)(string)$flags;
        $flags = array(
            'i' => ($flags & AeArray::SORT_FLAG_CI)    == AeArray::SORT_FLAG_CI,
            'k' => ($flags & AeArray::SORT_FLAG_KEY)   == AeArray::SORT_FLAG_KEY,
            'a' => ($flags & AeArray::SORT_FLAG_ASSOC) == AeArray::SORT_FLAG_ASSOC
        );

        // *** This takes care of any AeType objects in the array
        $this->_value = $this->getValue();

        if (is_callable($mode) || $mode instanceof AeCallback) {
            return $this->_sortCallback($mode, $flags);
        }

        return $this->_sort($mode, $flags);
    }

    private function _sortCallback($callback, $flags)
    {
        // TODO: add support for i flag

        if (!is_callable($callback) && $callback instanceof AeCallback) {
            // *** This happens in PHP 5.2
            $callback = $callback->getValue();
        }

        if ($flags['k']) {
            uksort($this->_value, $callback);
        } else if ($flags['a']) {
            uasort($this->_value, $callback);
        } else {
            usort($this->_value, $callback);
        }

        return $this;
    }

    private function _sort($mode, $flags)
    {
        $mode = AeType::unwrap($mode);

        switch ($mode)
        {
            case AeArray::SORT_NATURAL: {
                return $this->_sortNatural($flags);
            } break;

            case AeArray::SORT_NUMERIC: {
                $mode = SORT_NUMERIC;
            } break;

            case AeArray::SORT_STRING: {
                $mode = SORT_STRING;
            } break;

            case AeArray::SORT_LOCALE: {
                $mode = SORT_LOCALE_STRING;
            } break;

            case AeArray::SORT_REGULAR:
            default: {
                $mode = SORT_REGULAR;
            } break;
        }

        if ($flags['i']) {
            return $this->_sortCase($mode, $flags);
        } else if ($flags['k']) {
            ksort($this->_value, $mode);
        } else if ($flags['a']) {
            asort($this->_value, $mode);
        } else {
            sort($this->_value, $mode);
        }

        return $this;
    }

    private function _sortNatural($flags)
    {
        if ($flags['k'])
        {
            // *** Emulate sort by key
            $values = array_keys($this->_value);

            if ($flags['i']) {
                natcasesort($values);
            } else {
                natsort($values);
            }

            $result = array();

            foreach ($values as $k) {
                $result[$k] = $this->_value[$k];
            }

            $this->_value = $result;

            return $this;
        }

        if ($flags['i']) {
            natcasesort($this->_value);
        } else {
            natsort($this->_value);
        }

        if (!$flags['a']) {
            $this->_value = array_values($this->_value);
        }

        return $this;
    }

    private function _sortCase($mode, $flags)
    {
        if ($flags['k']) {
            $keys   = array_keys($this->_value);
            $values = $keys;
        } else {
            $values = $this->_value;
        }

        $mb = function_exists('mb_strtolower');

        foreach ($values as $k => $v) {
            $values[$k] = $mb ? mb_strtolower($v, 'UTF-8') : strtolower($v);
        }

        // *** We need the keys to restore the values
        asort($values, $mode);

        // *** Restore the real values
        $return = array();

        foreach ($values as $k => $v)
        {
            if ($flags['k']) {
                $return[$keys[$k]] = $this->_value[$keys[$k]];
            } else {
                $return[$k] = $this->_value[$k];
            }
        }

        if (!$flags['a'] && !$flags['k']) {
            $return = array_values($return);
        }

        $this->_value = $return;

        return $this;
    }

    public function splice($start, $length = null, $replacement = array())
    {
        if (!is_scalar($start) && !($start instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting start to be integer, ' . AeType::of($start) . ' given', 400);
        }

        $start = (int)(string)$start;

        if ($start >= $this->length) {
            throw new OutOfBoundsException('Start value outside array bounds', 416);
        }

        if ($length === null) {
            $length = $this->length;
        }

        if (!is_scalar($length) && !($length instanceof AeScalar)) {
            throw new InvalidArgumentException('Expecting length to be integer, ' . AeType::of($length) . ' given', 400);
        }

        $length = (int)(string)$length;

        if (func_num_args() > 3) {
            $replacement = func_get_args();
            $replacement = array_slice($replacement, 2);
        } else if (!is_array($replacement)) {
            $replacement = array($replacement);
        }

        return new AeArray(array_splice($this->_value, $start, $length, $replacement));
    }

    public function unshift($value)
    {
        $args  = func_get_args();

        foreach ($args as $arg) {
            array_unshift($this->_value, AeType::unwrap($arg));
        }

        return $this;
    }

    public function walk($callback)
    {
        if (!is_callable($callback) && !($callback instanceof AeCallback)) {
            throw new InvalidArgumentException('Expecting callback to be callable, ' . AeType::of($callback) . ' given', 400);
        }

        if (!is_callable($callback) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        foreach ($this->_value as $k => $v) {
            $this->_value[$k] = call_user_func($callback, $v, $k);
        }

        return $this;
    }

    public function offsetExists($offset)
    {
        $offset = AeType::unwrap($offset);

        if (!is_scalar($offset)) {
            throw new InvalidArgumentException('Expecting offset to be scalar, ' . AeType::of($offset) . ' given', 400);
        }

        return array_key_exists($offset, $this->_value);
    }

    public function offsetGet($offset)
    {
        $offset = AeType::unwrap($offset);

        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException('Offset value outside array bounds', 416);
        }

        return AeType::wrapReturn($this->_value[$offset]);
    }

    public function offsetSet($offset, $value)
    {
        $offset = AeType::unwrap($offset);

        if (is_null($offset)) {
            $this->_value[] = $value;
        } else {
            $this->_value[$offset] = $value;
        }

        return $this;
    }

    public function offsetUnset($offset)
    {
        $offset = AeType::unwrap($offset);

        if ($this->offsetExists($offset)) {
            unset($this->_value[$offset]);
        }

        return $this;
    }

    public function count()
    {
        return $this->getLength();
    }

    public function getIterator()
    {
        return new AeArray_Iterator($this);
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