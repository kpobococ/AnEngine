<?php
/**
 * Boolean class file
 *
 * See {@link AeBoolean} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Boolean class
 *
 * This class is a replacement for php's generic boolean type. Made for
 * type-hinting and OOP-styled function call purposes.
 *
 * @method bool getValue() getValue($default = null) Get a scalar boolean value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeBoolean extends AeScalar
{
    /**
     * Scalar boolean value
     * @var bool
     */
    protected $_value;

    /**
     * Boolean constructor
     *
     * @throws AeBooleanException #400 if the value passed is not a boolean
     *
     * @param bool $value
     */
    public function __construct($value = null)
    {
        if (!is_null($value) && !$this->setValue($value)) {
            throw new AeBooleanException('Invalid value passed: expecting null or bool, ' . gettype($value) . ' given', 400);
        }
    }

    /**
     * Set a boolean value
     *
     * @param bool $value
     *
     * @return bool true on valid value, false otherwise.
     */
    public function setValue($value)
    {
        if (is_string($value) && $value == 'false') {
            $value = false;
        }

        $value = (bool) $value;

        if (!is_bool($value)) {
            return false;
        }

        $this->_value = $value;

        return true;
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
        if (is_null($this->getValue())) {
            return $this->getClass() . '(null)';
        }

        return $this->getClass() . '(' . ($this->getValue() ? 'true' : 'false') . ')';
    }
}

/**
 * Boolean exception class
 *
 * Boolean-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeBooleanException extends AeScalarException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Boolean');
        parent::__construct($message, $code);
    }
}
?>