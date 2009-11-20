<?php
/**
 * Null class file
 *
 * See {@link AeNull} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Null class
 *
 * This class only serves one purpose: to return a value, consistent with the
 * current {@link AeType::$wrapReturn} setting value, if the latter is set to
 * true
 *
 * @method bool getValue() getValue($default = null) Get a scalar boolean value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeNull extends AeType
{
    /**
     * Set value
     *
     * This method does not set any values, but returns true only if passed
     * value is null
     *
     * @param null $value
     *
     * @return AeNull self
     */
    public function setValue($value)
    {
        return $this;
    }

    /**
     * Get value
     *
     * This method always returns the default value
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getValue($default = null)
    {
        return $default;
    }

    /**
     * Cast to string
     *
     * This method always returns an instance of AeString with its value set to
     * (string) 'null'
     *
     * @return AeString
     */
    public function toString()
    {
        return new AeString('null');
    }

    /**
     * String type cast support method
     *
     * This method is called every time an object is being cast to string (i.e.
     * echoed). It outputs 'null'
     *
     * @return string
     */
    public function __toString()
    {
        return 'null';
    }
}

/**
 * Null exception class
 *
 * Null-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeNullException extends AeTypeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Null');
        parent::__construct($message, $code);
    }
}
?>