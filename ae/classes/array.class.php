<?php
/**
 * Array class file
 *
 * See {@link AeArray} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Array class
 *
 * This class is a replacement for php's generic array type. Made for
 * OOP-styled function call purposes.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeArray extends AeType implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Wrap return values
     *
     * This property is the same as {@link AeType::$wrapReturn} except that it
     * only applies to array values
     *
     * @var bool
     */
    public static $wrapReturn = false;

    /**
     * Scalar array value
     * @var array
     */
    protected $_value;

    /**
     * Search from the beginning the array
     */
    const FIND_LEFT  = 1;

    /**
     * Search from the ending of the array
     */
    const FIND_RIGHT = 2;

    /**
     * Search from the beginning to the ending of the array
     */
    const FIND_ALL   = 4;

    /**
     * Return random keys
     */
    const RANDOM_KEYS   = 1;

    /**
     * Return random values
     */
    const RANDOM_VALUES = 2;

    // *** SORT MODES
    /**
     * Compare items without changing type
     */
    const SORT_REGULAR  = 1;

    /**
     * Compare items numerically
     */
    const SORT_NUMERIC  = 2;

    /**
     * Compare items as strings
     */
    const SORT_STRING   = 4;

    /**
     * Compare items as strings, based on the current locale
     */
    const SORT_LOCALE   = 8;

    /**
     * Compare items using a "natural order" algorithm
     */
    const SORT_NATURAL  = 16;

    // *** SORT FLAGS
    /**
     * Sort in reverse order
     */
    const SORT_REVERSE  = 1;

    /**
     * Sort by key
     */
    const SORT_BYKEY    = 2;

    /**
     * Sort and maintain index association
     */
    const SORT_SAVEKEYS = 4;

    /**
     * Sort using a case insensitive comparison
     */
    const SORT_NOCASE   = 8;

    /**
     * Array constructor
     *
     * There are several array construction methods currently supported:
     * <code> // Simple array
     * $array = new AeArray(1, 'two', true, 4.0);
     *
     * // Same as above
     * $array = new AeArray(array(1, 'two', true, 4.0));
     *
     * // Associative array
     * $array = new AeArray(array(0 => 1, 1 => 'two'));
     *
     * // Same as above
     * $array    = new AeArray;
     * $array[0] = 1;
     * $array[1] = 'two'; </code>
     *
     * @throws AeArrayException #400 if the value passed is not valid
     *
     * @param array|mixed $value       an array or first element of an array
     * @param mixed       $element,... elements of an array
     */
    public function __construct()
    {
        $count = func_num_args();

        switch ($count)
        {
            case 0: {
                $value = array();
            } break;

            case 1: {
                $value = func_get_arg(0);

                if ($value instanceof AeArray) {
                    $value = $value->getValue();
                }

                $value = (array) $value;
            } break;

            default: {
                $value = func_get_args();
            } break;
        }

        if (!is_null($value) && !$this->setValue($value)) {
            throw new AeArrayException('Invalid value passed: expecting null or array, ' . AeType::of($value) . ' given', 400);
        }
    }

    /**
     * Get range of elements
     *
     * Creates an array containing a range of elements, using the {@link http://php.net/range range()}
     * function.
     *
     * @see http://php.net/range
     *
     * @param AeScalar|mixed $start
     * @param AeScalar|mixed $end
     * @param AeScalar|mixed $step
     *
     * @return AeArray
     */
    public static function range($start, $end, $step = 1)
    {
        if ($start instanceof AeScalar) {
            $start = $start->getValue();
        }

        if ($end instanceof AeScalar) {
            $end = $end->getValue();
        }

        if ($step instanceof AeScalar) {
            $step = $step->getValue();
        }

        $range = @range($start, $end, $step);

        if (!$range) {
            $error = error_get_last();

            throw new AeArrayException($error['message'], 400);
        }

        return new AeArray($range);
    }

    /**
     * Set an array value
     *
     * @param array $value
     *
     * @return bool true on valid value, false otherwise.
     */
    public function setValue($value)
    {
        if (!is_array($value)) {
            return false;
        }

        $this->_value = $this->_safeValue($value);

        return true;
    }

    /**
     * Clear possible reference values
     *
     * This method clears any values, passed to an array by reference
     *
     * @param mixed $value
     *
     * @return mixed
     */
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

    /**
     * Get a scalar array value
     *
     * @param mixed $default
     *
     * @return array
     */
    public function getValue($default = null)
    {
        $_value = $this->_safeValue($this->_value);

        if ($_value !== null)
        {
            // *** Clear wrapper classes
            foreach ($_value as $key => $val)
            {
                if ($val instanceof AeType) {
                    $_value[$key] = $val->getValue();
                }
            }

            return $_value;
        }

        return $default;
    }

    /**
     * Join array elements with a string
     *
     * @see implode()
     *
     * @param string|AeString $glue
     *
     * @return AeString
     */
    public function join($glue = '')
    {
        if ($glue instanceof AeScalar) {
            $glue = $glue->toString()->getValue();
        }

        return new AeString(implode($glue, $this->getValue()));
    }

    /**
     * Exchanges keys with their values in an array
     *
     * @see array_flip()
     *
     * @return AeArray
     */
    public function flip()
    {
        return new AeArray(array_flip($this->getValue()));
    }

    /**
     * Push elements onto the end of the array
     *
     * Unlike {@link array_push()}, this method returns the new array.
     * Furthermore, the previous array is not modified:
     * <code> $arr1 = new AeArray(1, 2);
     * $arr2 = $arr1->push(3);
     *
     * print_r($arr1->getValue()); // Array(1, 2)
     * print_r($arr2->getValue()); // Array(1, 2, 3)</code>
     *
     * @see array_push()
     *
     * @param mixed|AeType $value,...
     *
     * @return AeArray
     */
    public function push($value)
    {
        $args  = func_get_args();
        $array = $this->getValue();

        foreach ($args as $arg)
        {
            if ($arg instanceof AeType) {
                $arg = $arg->getValue();
            }

            array_push($array, $arg);
        }

        return new AeArray($array);
    }

    /**
     * Pop the element off the end of array
     *
     * @see array_pop()
     *
     * @return AeType|null|object
     */
    public function pop()
    {
        $array  = $this->getValue();
        $return = array_pop($array);

        $this->setValue($array);

        return new AeArray($return);
    }

    /**
     * Prepend one or more elements to the beginning of the array
     *
     * Unlike {@link array_unshift()}, this method returns the new array.
     * Furthermore, the previous array is not modified:
     * <code> $arr1 = new AeArray(2, 3);
     * $arr2 = $arr1->unshift(1);
     *
     * print_r($arr1->getValue()); // Array(2, 3)
     * print_r($arr2->getValue()); // Array(1, 2, 3)</code>
     *
     * @see array_unshift()
     *
     * @param mixed|AeScalar $value,...
     *
     * @return AeArray
     */
    public function unshift($value)
    {
        $args  = func_get_args();
        $array = $this->getValue();

        foreach ($args as $arg)
        {
            if ($arg instanceof AeScalar || $arg instanceof AeArray) {
                $arg = $arg->getValue();
            }

            array_unshift($array, $arg);
        }

        return new AeArray($array);
    }

    /**
     * Shift an element off the beginning of the array
     *
     * @see array_shift()
     *
     * @return AeScalar|AeArray|null|object
     */
    public function shift()
    {
        $array  = $this->getValue();
        $return = array_shift($array);

        $this->setValue($array);

        return AeType::wrapReturn($return);
    }

    /**
     * Pick one or more random entries out of the array
     *
     * Random mode can be one of the following:
     *  - {@link AeArray::RANDOM_KEYS}   - return keys
     *  - {@link AeArray::RANDOM_VALUES} - (default) return key values
     *
     * If <var>$count</var> is greater than 1, an {@link AeArray} of keys or
     * values (depending on the <var>$mode</var>) is returned.
     *
     * If <var>$count</var> is 1, either mixed or AeScalar is returned
     *
     * @see array_rand()
     *
     * @throws AeArrayException #400 if count is less than 1
     *
     * @param int|AeInteger $count
     * @param int           $mode
     *
     * @return int|AeScalar|AeArray
     */
    public function random($count = 1, $mode = AeArray::RANDOM_VALUES)
    {
        if ($count instanceof AeScalar) {
            $count = $count->toInteger()->getValue();
        }

        if ($count < 1) {
            throw new AeArrayException('Count must be at least 1', 400);
        }

        $keys = array_rand($this->getValue(), $count);

        if ($mode == AeArray::RANDOM_VALUES)
        {
            if ($count == 1) {
                return $this[$keys];
            }

            $return = array();

            foreach ($keys as $offset) {
                $return[] = $this[$offset];
            }

            return AeType::wrapReturn($return);
        }

        if ($count == 1) {
            return $keys;
        }

        return AeType::wrapReturn($keys);
    }

    /**
     * Return an array with elements in reverse order
     *
     * @see array_reverse()
     *
     * @param bool|AeBoolean $preserve_keys
     *
     * @return AeArray
     */
    public function reverse($preserve_keys = false)
    {
        if ($preserve_keys instanceof AeScalar) {
            $preserve_keys = $preserve_keys->toBoolean()->getValue();
        }

        return new AeArray(array_reverse($this->getValue(), $preserve_keys));
    }

    /**
     * Walk array using callback
     *
     * Applies a user callback function to every element of an array and return
     * the modified array:
     * <code> $array = new AeArray(array('foo' => 'Foo value', 'bar' => 'Bar value'));
     *
     * function myWalkFunction($value, $key)
     * {
     *     echo "the value of the '$key' key is '$value'\n";
     *     return $value;
     * }
     *
     * $array->walk(new AeCallback('myWalkFunction'), true);</code>
     *
     * The callback function may take as many as two parameters: the current
     * element value and the current element key respectively. If it returns any
     * value, this value will be used as the new element value.
     *
     * This method does not modify the initial array, but performs all
     * modifications on it's copy:
     * <code> $arr1 = new AeArray('FOO');
     * $arr2 = $arr1->walk(new AeCallback('strtolower'));
     *
     * echo $arr1[0] == $arr2[0] ? 'true' : 'false'; // false</code>
     *
     * @param string|array|AeCallback $callback
     * @param bool|AeBoolean          $passKey   should the element key be passed
     *                                           to the callback function or not
     *
     * @return AeArray
     */
    public function walk($callback, $passKey = false)
    {
        if (!($callback instanceof AeCallback)) {
            $callback = new AeCallback($callback);
        }

        if ($passKey instanceof AeScalar) {
            $passKey = $passKey->toBoolean()->getValue();
        }

        $array = clone $this;

        foreach ($array as $key => $value)
        {
            if ((bool) $passKey) {
                $v = @$callback->call($value, $key);
            } else {
                $v = @$callback->call($value);
            }

            if ($v !== null) {
                $array[$key] = $v;
            }
        }

        return $array;
    }

    /**
     * Sort an array
     *
     * Sort mode can be one of the following:
     *  - {@link AeArray::SORT_REGULAR} - (default) compare items without changing types
     *  - {@link AeArray::SORT_NUMERIC} - compare items numerically
     *  - {@link AeArray::SORT_STRING}  - compare items as strings
     *  - {@link AeArray::SORT_LOCALE}  - compare items as strings, based on the current locale
     *  - {@link AeArray::SORT_NATURAL} - compare items using a "natural order" algorithm
     *
     * Sort flags can be one or a sum of the following:
     *  - {@link AeArray::SORT_REVERSE}  - sort the array in reverse order
     *  - {@link AeArray::SORT_BYKEY}    - sort the array by key
     *  - {@link AeArray::SORT_SAVEKEYS} - sort and maintain index association
     *  - {@link AeArray::SORT_NOCASE}   - sort the array using a case insensitive comparison
     *
     * <b>Examples:</b>
     * <code> // Regular sort
     * $array = new AeArray('img2', 'img10', 'img3');
     * print_r($array->sort()->getValue());
     * // Result: Array('img10', 'img2', 'img3')
     *
     * // Natural sort
     * print_r($array->sort(AeArray::SORT_NATURAL)->getValue());
     * // Result: Array('img2', 'img3', 'img10')
     *
     * // Flags
     * $array = new AeArray('Img2', 'img10', 'IMG3');
     * print_r($array->sort()->getValue());
     * // Result: Array('IMG3', 'Img2', 'img10');
     *
     * print_r($array->sort(AeArray::SORT_NATURAL, AeArray::SORT_NOCASE)->getValue());
     * // Result: Array('Img2', 'IMG3', 'img10');
     *
     * print_r($array->sort(AeArray::SORT_NATURAL, AeArray::SORT_NOCASE + AeArray::SORT_REVERSE)->getValue());
     * // Result: Array('img10', 'IMG3', 'Img2');</code>
     *
     * Unlike {@link sort()} and other array sorting functions, this method
     * returns the new array. Furthermore, the previous array is not modified:
     * <code> $arr1 = new AeArray(2, 3, 1);
     * $arr2 = $arr1->sort();
     *
     * print_r($arr1->getValue()); // Array(2, 3, 1)
     * print_r($arr2->getValue()); // Array(1, 2, 3)</code>
     *
     * @see natsort(), natcasesort()
     * @see sort(), rsort(), ksort(), krsort(), asort(), arsort()
     *
     * @throws AeArrayException #400 if mode is invalid
     *
     * @param int $mode
     * @param int $flags
     *
     * @return AeArray
     */
    public function sort($mode = null, $flags = 0)
    {
        if ($mode === null) {
            $mode = AeArray::SORT_REGULAR;
        }

        if ($flags > 0) {
            $reverse  = ($flags & AeArray::SORT_REVERSE)  == AeArray::SORT_REVERSE;
            $bykey    = ($flags & AeArray::SORT_BYKEY)    == AeArray::SORT_BYKEY;
            $savekeys = ($flags & AeArray::SORT_SAVEKEYS) == AeArray::SORT_SAVEKEYS;
            $nocase   = ($flags & AeArray::SORT_NOCASE)   == AeArray::SORT_NOCASE;
        }

        if ($mode == AeArray::SORT_NATURAL) {
            return $this->_sortNatural($bykey, $nocase, $reverse, $savekeys);
        }

        $modes = array(
            AeArray::SORT_REGULAR,
            AeArray::SORT_NUMERIC,
            AeArray::SORT_STRING,
            AeArray::SORT_LOCALE
        );

        if (!in_array($mode, $modes)) {
            throw new AeArrayException('Mode value is invalid', 400);
        }

        return $this->_sort($mode, $bykey, $nocase, $reverse, $savekeys);
    }

    /**
     * Regular sort
     *
     * @param int  $mode
     * @param bool $bykey
     * @param bool $nocase
     * @param bool $reverse
     * @param bool $savekeys
     *
     * @return AeArray
     */
    private function _sort($mode, $bykey, $nocase, $reverse, $savekeys)
    {
        // *** Set the correct PHP sort mode
        switch ($mode)
        {
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

        if ($nocase) {
            // *** Call case-insensitive search
            return $this->_sortNoCase($mode, $bykey, $reverse, $savekeys);
        }

        $values = $this->getValue();

        if ($bykey)
        {
            // *** Sort array by key
            if ($reverse) {
                krsort($values, $mode);
            } else {
                ksort($values, $mode);
            }

            if (!$savekeys) {
                // *** Discard array keys
                $values = array_values($values);
            }

            return new AeArray($values);
        }

        if ($savekeys)
        {
            // *** Sort array saving the keys
            if ($reverse) {
                arsort($values, $mode);
            } else {
                asort($values, $mode);
            }

            return new AeArray($values);
        }

        if ($reverse) {
            rsort($values, $mode);
        } else {
            sort($values, $mode);
        }

        return new AeArray($values);
    }

    /**
     * Case-insensitive sort
     *
     * @param int  $mode
     * @param bool $bykey
     * @param bool $reverse
     * @param bool $savekeys
     *
     * @return AeArray
     */
    private function _sortNoCase($mode, $bykey, $reverse, $savekeys)
    {
        if ($bykey) {
            $values = $this->getKeys()->getValue();
        } else {
            $values = $this->getValue();
        }

        // *** Make values case-insensitive
        foreach ($values as $key => $value) {
            $values[$key] = strtolower($value);
        }

        // *** Sort using savekeys modifier
        if ($reverse) {
            arsort($values, $mode);
        } else {
            asort($values, $mode);
        }

        // *** Restore the real values
        if ($bykey)
        {
            $return = array();
            $keys   = $this->getKeys()->getValue();

            foreach ($values as $i => $key) {
                $return[$keys[$i]] = $this[$keys[$i]];
            }
        } else {
            $return = array();

            foreach ($values as $key => $value) {
                $return[$key] = $this[$key];
            }
        }

        if (!$savekeys) {
            // *** Discard keys
            return new AeArray(array_values($return));
        }

        return new AeArray($return);
    }

    /**
     * Sort using a "natural order" algorithm
     *
     * @param bool $bykey
     * @param bool $nocase
     * @param bool $reverse
     * @param bool $savekeys
     *
     * @return AeArray
     */
    private function _sortNatural($bykey, $nocase, $reverse, $savekeys)
    {
        if ($bykey) {
            // *** Sort keys
            $value = $this->getKeys()->getValue();
        } else {
            // *** Sort values
            $value = $this->getValue();
        }

        if ($nocase) {
            // *** Case-insensitive sort
            natcasesort($value);
        } else {
            // *** Regular sort
            natsort($value);
        }

        if ($bykey)
        {
            // *** Assign values to the keys in the resulting array
            $result = array();

            foreach ($value as $key) {
                $result[$key] = $this[$key];
            }
        } else {
            // *** Assign sorted array to result array
            $result = $value;
        }

        if ($reverse) {
            // *** Reverse the array
            $result = array_reverse($result, $savekeys);
        }

        if (!$savekeys) {
            // *** Discard keys
            $result = array_values($result);
        }

        return new AeArray($result);
    }

    /**
     * Return keys of an array
     *
     * @see array_keys()
     *
     * @return AeArray
     */
    public function getKeys()
    {
        return new AeArray(array_keys($this->getValue()));
    }

    /**
     * Return values of an array
     *
     * @see array_values()
     *
     * @return AeArray
     */
    public function getValues()
    {
        return new AeArray(array_values($this->getValue()));
    }

    /**
     * Return first value
     *
     * @return AeType|mixed
     */
    public function getFirst()
    {
        if ($this->length() == 0) {
            return AeType::wrapReturn(null);
        }

        $keys = array_keys($this->getValue());

        return $this->offsetGet($keys[0]);
    }

    /**
     * Return last value
     *
     * @return AeType|mixed
     */
    public function getLast()
    {
        $length = $this->length();

        if ($length == 0) {
            return AeType::wrapReturn(null);
        }

        $keys = array_keys($this->getValue());

        return $this->offsetGet($keys[$length - 1]);
    }

    /**
     * Return index of a value
     *
     * Find mode can be one of the following:
     *  - {@link AeArray::FIND_LEFT}  - (default) find first index of a value
     *  - {@link AeArray::FIND_RIGHT} - find last index of a value
     *  - {@link AeArray::FIND_ALL}   - find all indexes of a value (array is returned)
     *
     * If a value is not present in the array, false is returned
     *
     * This method returns scalar integer instead of {@link AeInteger} instance.
     * This is due to the fact, that the method does not manipulate the array
     * in any way but is informational.
     *
     * @see array_keys(), array_search()
     *
     * @param mixed|AeScalar $needle
     * @param int            $mode
     * @param bool|AeBoolean $strict
     *
     * @return bool|int|array
     */
    public function find($needle, $mode = AeArray::FIND_LEFT, $strict = false)
    {
        if ($needle instanceof AeScalar) {
            $needle = $needle->getValue();
        }

        if ($strict instanceof AeScalar) {
            $strict = $strict->toBoolean()->getValue();
        }

        switch ($mode)
        {
            case AeArray::FIND_RIGHT: {
                $return = array_keys($this->getValue(), $needle, $strict);
                return count($return) > 0 ? end($return) : false;
            } break;

            case AeArray::FIND_ALL: {
                $return = array_keys($this->getValue(), $needle, $strict);
                return count($return) > 0 ? $return : false;
            } break;

            case AeArray::FIND_LEFT:
            default: {
                return array_search($needle, $this->getValue(), $strict);
            } break;
        }
    }

    /**
     * Return array element count
     *
     * This method is an alias of {@link AeArray::count() count()}
     * 
     * This method returns scalar integer instead of {@link AeInteger} instance.
     * This is due to the fact, that the method does not manipulate the array
     * in any way but is informational.
     *
     * The optional <var>filter</var> parameter can be used to count certain
     * classes inside the array and should contain a class name
     *
     * @return int
     */
    public function length()
    {
        return count($this->_value);
    }

    /**
     * String type cast support method
     *
     * This method is called every time an object is being cast to string (i.e.
     * echoed).
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getClass() . '(' . $this->length() . ')';
    }

    /**
     * Whether an offset exists
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @param mixed|AeScalar $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->getValue();
        }

        return isset($this->_value[$offset]);
    }

    /**
     * Return offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @param mixed|AeScalar $offset
     *
     * @return AeScalar|AeArray|mixed
     */
    public function offsetGet($offset)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->getValue();
        }

        if (!$this->offsetExists($offset)) {
            // *** Support ArrayAccess by returning an array if value is not set
            $this->_value[$offset] = new AeArray;
            return $this->_value[$offset];
        }

        return AeType::wrapReturn($this->_value[$offset]);
    }

    /**
     * Set offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @param mixed|AeScalar $offset
     * @param mixed          $value
     *
     * @return AeArray self
     */
    public function offsetSet($offset, $value)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->getValue();
        }

        if (is_null($offset)) {
            $this->_value[] = $value;
        } else {
            $this->_value[$offset] = $value;
        }

        return $this;
    }

    /**
     * Unset offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @param mixed|AeScalar $offset
     * 
     * @return AeArray self
     */
    public function offsetUnset($offset)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->getValue();
        }

        unset($this->_value[$offset]);

        return $this;
    }

    /**
     * Return array element count
     *
     * This method returns scalar integer instead of {@link AeInteger} instance.
     * This is due to the fact, that the method does not manipulate the array
     * in any way but is informational.
     * 
     * Method for the {@link Countable} interface implementation
     *
     * @return int
     */
    public function count()
    {
        return $this->length();
    }

    /**
     * Return array iterator
     *
     * Return an object instance of a class, implementing an Iterator interface,
     * used for array iterations using foreach and similar structures.
     *
     * Method for the {@link IteratorAggregate} interface implementation
     *
     * @uses AeArray_Iterator::getInstance()
     *
     * @return AeArray_Iterator
     */
    public function getIterator()
    {
        return AeArray_Iterator::getInstance($this);
    }

    protected static function _wrapReturn($value)
    {
        if (self::$wrapReturn === true && !($value instanceof AeArray)) {
            return new AeArray($value);
        }

        if (self::$wrapReturn === false && $value instanceof AeArray) {
            $value = $value->getValue();
        }

        return $value;
    }
}

/**
 * Array exception class
 *
 * Array-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeArrayException extends AeTypeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Array');
        parent::__construct($message, $code);
    }
}
?>