<?php
/**
 * Scalar class file
 *
 * See {@link AeScalar} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Scalar abstract class
 *
 * This class is a basic OOP type replacement for PHP's scalar data types. Made
 * for type-hinting and OOP-styled function call purposes.
 *
 * @method mixed getValue() getValue($default = null) Get a scalar value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
abstract class AeScalar extends AeType
{
    /**
     * Scalar value
     * @var mixed
     */
    protected $_value;

    /**
     * Wrap return values
     *
     * This property is the same as {@link AeType::$wrapReturn} except that it
     * only applies to scalar values
     *
     * @var bool
     *
     * @todo check all libs to add support for this parameter
     */
    public static $wrapReturn = false;

    /**
     * Wrap value in AeScalar instance
     *
     * This method selects the respective class and wraps the passed value using
     * that class. If the value passed is not a scalar value, false is returned.
     * Currently, only boolean, integer, float and string values are scalar.
     *
     * @param mixed $value
     *
     * @return AeScalar|false
     */
    public static function wrap($value)
    {
        if ($value instanceof AeScalar) {
            return $value;
        }

        if (is_scalar($value))
        {
            switch (AeType::of($value))
            {
                case 'boolean': {
                    return new AeBoolean($value);
                } break;

                case 'integer': {
                    return new AeInteger($value);
                } break;

                case 'float': {
                    return new AeFloat($value);
                } break;

                case 'string': {
                    return new AeString($value);
                } break;
            }
        }

        throw new AeScalarException('Invalid value type: expecting scalar, ' . AeType::of($value) . ' given', 400);
    }

    /**
     * Set a value
     *
     * @todo return self
     *
     * @param mixed $value
     *
     * @return bool
     */
    abstract public function setValue($value);

    /**
     * Cast to string
     *
     * Return a string value wrapped in {@link AeString} class instance
     *
     * @return AeString
     */
    public function toString()
    {
        if ($this instanceof AeString) {
            return $this;
        }

        if (is_null($this->getValue())) {
            return new AeString(null);
        }

        return new AeString($this->getValue());
    }

    /**
     * Cast to float
     *
     * Return a float value wrapped in {@link AeFloat} class instance
     *
     * @return AeFloat
     */
    public function toFloat()
    {
        if ($this instanceof AeFloat) {
            return $this;
        }

        if (is_null($this->getValue())) {
            return new AeFloat(null);
        }

        return new AeFloat($this->getValue());
    }

    /**
     * Cast to integer
     *
     * Return an integer value wrapped in {@link AeInteger} class instance
     *
     * @return AeInteger
     */
    public function toInteger()
    {
        if ($this instanceof AeInteger) {
            return $this;
        }

        if (is_null($this->getValue())) {
            return new AeInteger(null);
        }

        return new AeInteger($this->getValue());
    }

    /**
     * Cast to boolean
     *
     * Return a boolean value wrapped in {@link AeBoolean} class instance
     *
     * @return AeBoolean
     */
    public function toBoolean()
    {
        if ($this instanceof AeBoolean) {
            return $this;
        }

        if (is_null($this->getValue())) {
            return new AeBoolean(null);
        }

        return new AeBoolean($this->getValue());
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
        return $this->toString()->getValue('null');
    }

    protected static function _wrapReturn($value)
    {
        if (self::$wrapReturn === true && !($value instanceof AeScalar)) {
            return self::wrap($value);
        }

        if (self::$wrapReturn === false && $value instanceof AeScalar) {
            $value = $value->getValue();
        }

        return $value;
    }
}

/**
 * Scalar exception class
 *
 * Scalar-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeScalarException extends AeTypeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Scalar');
        parent::__construct($message, $code);
    }
}
?>