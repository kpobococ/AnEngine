<?php
/**
 * Directory iterator class file
 *
 * See {@link AeDirectory_Iterator} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Directory iterator class
 *
 * This class is an addition to {@link AeDirectory} class. It implements the SPL
 * Iterator interface, enabling the user to iterate through the AeDirectory as
 * if it was a regular array of files.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeDirectory_Iterator extends AeObject implements Iterator
{
    /**
     * Current element value
     * @var mixed
     */
    protected $_current = null;

    /**
     * Current element key
     * @var mixed
     */
    protected $_key = null;

    /**
     * AeDirectory object the iterator is attached to
     * @var AeDirectory
     */
    protected $_directory;

    /**
     * Return iterator instance
     *
     * Returns the iterator instance for a certain AeDirectory object instance.
     * This makes sure we won't have several instances of iterators for the same
     * AeDirectory object. You can still get several instances by cloning the
     * iterator, though.
     *
     * @param AeDirectory $directory
     *
     * @return AeDirectory_Iterator
     */
    public static function getInstance(AeDirectory $directory)
    {
        return AeInstance::get('AeDirectory_Iterator', array($directory), true, false);
    }

    /**
     * Constructor
     *
     * @param AeDirectory $directory
     */
    public function __construct(AeDirectory $directory)
    {
        $this->_directory = $directory;
        $this->rewind();
    }

    /**
     * Rewind the Iterator
     *
     * Rewinds the Iterator to the first element and returns its value
     *
     * Method for the {@link Iterator} interface implementation
     *
     * @uses AeDirectory_Iterator::current() to return the value of the element
     *
     * @return AeObject_File first element or null, if array is empty
     */
    public function rewind()
    {
        $dh = $this->_directory->getHandle();

        @rewinddir($dh);

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
     * @return AeObject_File current element or null, if array is empty
     */
    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        return AeObject_File::wrap($this->_directory->path . SLASH . $this->_current);
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
     * @uses AeDirectory_Iterator::current() to return the value of the element
     *
     * @return AeObject_File next element or null, if end of array was reached
     */
    public function next()
    {
        $this->_readNext();

        return $this->current();
    }

    /**
     * Check for current element
     *
     * Checks if there is a current element after calls to {@link
     * AeDirectory_Iterator::rewind() rewind()} or {@link
     * AeDirectory_Iterator::next() next()}
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
     * like {@link AeDirectory_Iterator::next() next()} and {@link
     * AeDirectory_Iterator::rewind() rewind()} use it to verify if the current
     * value exists. The {@link AeDirectory_Iterator::valid() valid()} method
     * only works with the results, this method provides.
     *
     * @return mixed current element key or null, if end of array was reached
     */
    protected function _readNext()
    {
        $dh = $this->_directory->getHandle();

        do {
            $current = @readdir($dh);
        } while ($current == '.' || $current == '..');

        if ($current === false) {
            $this->_current = null;
            $this->_key     = null;

            return null;
        }

        $this->_current = $current;

        if ($this->_key === null) {
            $this->_key = 0;
        } else {
            $this->_key++;
        }

        return $this->_key;
    }
}

/**
 * Directory iterator exception class
 *
 * Directory iterator-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDirectoryIteratorException extends AeDirectoryException
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