<?php
/**
 * Array iterator class file
 *
 * See {@link AeArray_Iterator} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Array iterator class
 *
 * This class is an addition to {@link AeArray} class. It implements the SPL
 * Iterator interface, enabling the user to iterate through the AeArray as if it
 * was a regular array.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeArray_Iterator extends AeObject implements Iterator
{
    /**
     * Current element key
     * @var mixed
     */
    protected $_key;

    /**
     * AeArray object the iterator is attached to
     * @var AeArray
     */
    protected $_array;

    /**
     * Array keys for internal iteration
     * @var array
     */
    protected $_keys;

    /**
     * Return iterator instance
     *
     * Returns the iterator instance for a certain AeArray object instance. This
     * makes sure we won't have several instances of iterators for the same
     * AeArray object. You can still get several instances by cloning the
     * iterator, though.
     *
     * @param AeArray $array
     *
     * @return AeArray_Iterator
     */
    public static function getInstance(AeArray $array)
    {
        return AeInstance::get('AeArray_Iterator', array($array), true, false);
    }

    /**
     * Constructor
     *
     * @param AeArray $array
     */
    public function __construct(AeArray $array)
    {
        $this->_array = $array;

        $this->rewind();
    }

    /**
     * Rewind the Iterator
     *
     * Rewinds the Iterator to the first element and returns its value
     *
     * Method for the {@link Iterator} interface implementation
     *
     * @uses AeArray_Iterator::current() to return the value of the element
     *
     * @return AeType first element or null, if array is empty
     */
    public function rewind()
    {
        $this->_keys = $this->_array->getKeys()->getValue();

        reset($this->_keys);

        $this->_readNext();

        return $this->current();
    }

    /**
     * Return current element
     *
     * Returns the current element value inside wrapper object
     *
     * Method for the {@link Iterator} interface implementation
     *
     * @return AeType current element or null, if array is empty
     */
    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        return $this->_array->offsetGet($this->_key);
    }

    /**
     * Return current element key
     *
     * Returns the key of the current element.
     *
     * This method returns a scalar value instead of wrapping it inside one of
     * the AeScalar classes. This is to maintain compatibility with PHP's loop
     * structures.
     *
     * Method for the {@link Iterator} interface implementation
     *
     * @return mixed
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     * Move to next element
     *
     * Moves forward to the next element and returns its value
     *
     * Method for the {@link Iterator} interface implementation
     *
     * @uses AeArray_Iterator::current() to return the value of the element
     *
     * @return AeType next element or null, if end of array was reached
     */
    public function next()
    {
        next($this->_keys);

        $this->_readNext();

        return $this->current();
    }

    /**
     * Check for current element
     *
     * Checks if there is a current element after calls to {@link
     * AeArray_Iterator::rewind() rewind()} or {@link AeArray_Iterator::next()
     * next()}
     *
     * Method for the {@link Iterator} interface implementation
     *
     * @return bool
     */
    public function valid()
    {
        return ($this->_key === null) ? false : true;
    }

    /**
     * Read next value
     *
     * This method is called after each internal array pointer movement. Methods
     * like {@link AeArray_Iterator::next() next()} and {@link
     * AeArray_Iterator::rewind() rewind()} use it to verify if the current
     * value exists. The {@link AeArray_Iterator::valid() valid()} method only
     * works with the results, this method provides.
     *
     * @return mixed current element key or null, if end of array was reached
     */
    protected function _readNext()
    {
        $key   = current($this->_keys); // FALSE if end of array
        $valid = key($this->_keys) === null ? false : true;

        if ($valid === false && $key === false) {
            // *** No element, set all to null
            $this->_key = null;
        } else {
            $this->_key = $key;
        }

        return $key;
    }
}

/**
 * Array iterator exception class
 *
 * Array iterator-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeArrayIteratorException extends AeArrayException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Iterator');
        parent::__construct($message, $code);
    }
}
?>