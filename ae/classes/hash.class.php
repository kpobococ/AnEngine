<?php
/**
 * Hash class file
 *
 * See {@link AeHash} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Hash class
 *
 * A hash is essentially an associative array. This class requires all array
 * keys to be strings and not be numeric strings (e.g. '1') and overrides some
 * basic {@link AeArray} methods to enforce this behavior.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeHash extends AeArray
{
    /**
     * Hash constructor
     *
     * There is only one hash construction method currently supported:
     * <code> // Simple hash
     * $array = new AeHash(array('foo' => 1, 'bar' => 2));
     *
     * // Same as above
     * $array        = new AeHash;
     * $array['foo'] = 1;
     * $array['bar'] = 2; </code>
     *
     * @throws AeHashException #400 if the value passed is not valid
     *
     * @param array $value an associative array
     */
    public function __construct($value = null)
    {
        if ($value instanceof AeArray) {
            $value = $value->getValue();
        }

        if (!is_null($value) && !$this->setValue($value)) {
            throw new AeHashException('Invalid value passed: expecting null or hash, ' . AeType::typeOf($value) . ' given', 400);
        }
    }

    /**
     * Set a hash value
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

        foreach (array_keys($value) as $key)
        {
            if (!is_string($key) || is_numeric($key)) {
                return false;
            }
        }

        $this->_value = $value;

        return true;
    }

    /**
     * Set offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * Note, that this method does not support key auto-creation, as {@link
     * AeArray}, meaning that the following struct will throw an exception:
     * <code> $hash[] = 'foo';</code>
     *
     * @throws AeHashException #400 if offset type is not a string
     * @throws AeHashException #400 if offset value is numeric
     *
     * @param mixed|AeType $offset
     * @param mixed        $value
     *
     * @return AeHash self
     */
    public function offsetSet($offset, $value)
    {
        if ($offset instanceof AeType) {
            $offset = $offset->getValue();
        }

        if (!is_string($offset)) {
            throw new AeHashException('Invalid offset type: expecting string, ' . AeType::typeOf($offset) . ' given', 400);
        }

        if (is_numeric($offset)) {
            throw new AeHashException('Invalid offset value: value cannot be numeric', 400);
        }

        return parent::offsetSet($offset, $value);
    }
}

/**
 * Hash exception class
 *
 * Hash-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeHashException extends AeArrayException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Hash');
        parent::__construct($message, $code);
    }
}
?>