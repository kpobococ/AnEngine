<?php
/**
 * Sequence class file
 *
 * See {@link AeSequence} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Sequence class
 *
 * A sequence is an array that has integer keys starting with 0 and each next
 * element key incremented by one. This class overrides some basic {@link
 * AeArray} methods to enforce this behavior.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeSequence extends AeArray
{
    /**
     * Sequence constructor
     *
     * There are several sequence construction methods currently supported:
     * <code> // Simple sequence
     * $array = new AeSequence(1, 'two', true, 4.0);
     *
     * // Same as above
     * $array = new AeSequence(array(1, 'two', true, 4.0));
     *
     * // Associative array
     * $array = new AeSequence(array(0 => 1, 1 => 'two'));
     *
     * // Same as above
     * $array    = new AeSequence;
     * $array[0] = 1;
     * $array[1] = 'two'; </code>
     *
     * @throws AeSequenceException #400 if the value passed is not valid
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
            throw new AeSequenceException('Invalid value passed: expecting null or sequence, ' . AeType::typeOf($value) . ' given', 400);
        }
    }

    /**
     * Set a sequence value
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

        $value = $this->_safeValue($value);
        $count = count($value);

        if ($count > 0)
        {
            if ($count == 1 && array_shift(array_keys($value)) != 0) {
                return false;
            }

            if (array_keys($value) != range(0, $count - 1)) {
                return false;
            }
        }

        $this->_value = $value;

        return true;
    }

    /**
     * Get range of elements
     *
     * Creates a sequence containing a range of elements, using the
     * {@link http://php.net/range range()} function.
     *
     * @see http://php.net/range
     *
     * @param AeScalar|mixed $start
     * @param AeScalar|mixed $end
     * @param AeScalar|mixed $step
     *
     * @return AeSequence
     */
    public static function range($start, $end, $step = 1)
    {
        return new AeSequence(parent::range($start, $end, $step));
    }

    /**
     * Return first value
     *
     * This method is optimized to work with sequences
     *
     * @return AeType|mixed
     */
    public function getFirst()
    {
        if ($this->length() == 0) {
            return AeType::wrapReturn(null);
        }

        return $this->offsetGet(0);
    }

    /**
     * Return last value
     *
     * This method is optimized to work with sequences
     *
     * @return AeType|mixed
     */
    public function getLast()
    {
        $length = $this->length();

        if ($length == 0) {
            return AeType::wrapReturn(null);
        }

        return $this->offsetGet($length - 1);
    }

    /**
     * Set offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @throws AeSequenceException #400 if offset type is not an integer or null
     * @throws AeSequenceException #400 if offset value is less than zero or
     *                                  greater than current sequence length
     *                                  (this still allows adding new elements
     *                                  to the sequence, as the sequence starts
     *                                  from zero)
     *
     * @param mixed|AeType $offset
     * @param mixed        $value
     *
     * @return AeSequence self
     */
    public function offsetSet($offset, $value)
    {
        if ($offset instanceof AeType) {
            $offset = $offset->getValue();
        }

        if (is_null($offset)) {
            return parent::offsetSet(null, $value);
        }

        if (!is_numeric($offset)) {
            throw new AeSequenceException('Invalid offset type: expecting null or integer, ' . AeType::typeOf($offset) . ' given', 400);
        }

        $offset = (int) $offset;

        if ($offset < 0) {
            throw new AeSequenceException('Invalid offset value: value must be greater than or equal to zero', 400);
        } else if ($offset > $this->length()) {
            throw new AeSequenceException('Invalid offset value: value must not exceed current array length', 400);
        }

        return parent::offsetSet($offset, $value);
    }

    /**
     * Unset offset value
     *
     * If an offset is removed from the beginning or the middle of an array, all
     * the values, following the offset, are reindexed
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @param mixed|AeScalar $offset
     *
     * @return AeSequence self
     */
    public function offsetUnset($offset)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->getValue();
        }

        unset($this->_value[$offset]);

        $this->_value = array_values($this->_value);

        return $this;
    }
}

/**
 * Sequence exception class
 *
 * Sequence-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSequenceException extends AeArrayException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Sequence');
        parent::__construct($message, $code);
    }
}
?>