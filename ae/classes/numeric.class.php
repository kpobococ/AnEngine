<?php
/**
 * Numeric class file
 *
 * See {@link AeNumeric} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Numeric abstract class
 *
 * This class is a basic OOP type replacement for PHP's numeric data types. Made
 * for type-hinting and OOP-styled function call purposes.
 *
 * @method mixed getValue() getValue($default = null) Get a numeric value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
abstract class AeNumeric extends AeScalar
{
    /**
     * Numeric value
     * @var mixed
     */
    protected $_value;

    /**
     * Wrap value in AeNumeric instance
     *
     * This method selects the respective class and wraps the passed value using
     * that class. If the value passed is not a numeric value, false is
     * returned. Numeric value are integer, float and numeric string values.
     *
     * @param mixed $value
     *
     * @return AeScalar|false
     */
    public static function wrap($value)
    {
        if ($value instanceof AeNumeric) {
            return $value;
        }

        if ($value instanceof AeString) {
            $value = $value->getValue();
        }

        if (is_numeric($value))
        {
            switch (gettype($value))
            {
                case 'integer': {
                    return new AeInteger($value);
                } break;

                case 'double': {
                    return new AeFloat($value);
                } break;

                case 'string':
                {
                    if ($value == round($value) && $value >= AeInteger::MIN && $value <= AeInteger::MAX) {
                        return new AeInteger($value);
                    }

                    return new AeFloat($value);
                } break;
            }
        }

        throw new AeNumericException('Invalid value type: expecting numeric, ' . AeType::typeOf($value) . ' given', 400);
    }
}

/**
 * Numeric exception class
 *
 * Numeric-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeNumericException extends AeScalarException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Numeric');
        parent::__construct($message, $code);
    }
}
?>