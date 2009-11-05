<?php
/**
 * Type class file
 *
 * See {@link AeType} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Type abstract class
 *
 * This type is the framework's basic data type. It has several useful methods
 * and properties, that are used throughout the whole framework
 *
 * @todo consider adding an unwrap() static method
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
abstract class AeType extends AeObject
{
    /**
     * Wrap return values
     *
     * This value defines how AnEngine libraries will behave when returning
     * values. There are three possible values for this property:
     * - null  (default) Values are wrapped according to AeType subclass
     *         settings (see below)
     * - true  Values are wrapped using AeType child class instances, like
     *         {@link AeString} or {@link AeArray}
     * - false Values are not wrapped
     *
     * The wrapping is also applied to default values.
     *
     * {@link AeScalar} and {@link AeArray} each has a property with the same
     * name. If this property is set to null for AeType, the values of all
     * subsequent properties are checked. This way you can choose, what data
     * you want wrapped.
     *
     * Also not, that a false value will result in unwrapping the values from
     * any classes they might be wrapped in. This also affects the default
     * values.
     *
     * @var bool
     *
     * @todo check all libs to add support for this parameter
     */
    public static $wrapReturn = true;

    /**
     * Wrap value in AeType instance
     *
     * This method selects the respective class and wraps the passed value using
     * that class. If the value passed is not a type value, false is returned.
     * Type values are null, boolean, integer, float, string and array values.
     *
     * @param mixed $value
     *
     * @return AeType|false
     */
    public static function wrap($value)
    {
        if ($value instanceof AeType) {
            return $value;
        }

        if (is_null($value)) {
            return new AeNull;
        }

        if (is_scalar($value)) {
            return AeScalar::wrap($value);
        }

        if (is_array($value)) {
            return new AeArray($value);
        }

        return false;
    }

    /**
     * Get type of value
     *
     * This method returns data type of the value passed. This method will
     * return class name for any of the {@link AeType} classes. The purpose of
     * this method is to correctly detect the type of scalar properties,
     * specifically float values, as {@link http://php.net/gettype gettype()}
     * returns double instead of float for these values.
     *
     * If you need to know if a value is an instance of a certain AeType child
     * class, use the keyword <b>instanceof</b> instead:
     * <code> if ($value instanceof AeString) {
     *     echo 'Value is string';
     * }
     *
     * // *** This is also valid:
     * if ($value instanceof AeScalar) {
     *     echo 'Value is scalar';
     * }</code>
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function of($value)
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_scalar($value) && is_float($value)) {
            return 'float';
        }

        if (is_object($value))
        {
            if ($value instanceof AeType) {
                return self::of($value->getValue());
            }

            return 'object';
        }

        return gettype($value);
    }

    /**
     * Wrap return
     *
     * This method wraps (or unwraps) the value passed according to all the
     * <var>$wrapReturn</var> settings.
     *
     * @param mixed $value
     * @param mixed $default
     *
     * @return mixed
     */
    public static function wrapReturn($value, $default = null)
    {
        if ($value instanceof AeType) {
            $value = $value->getValue() === null ? $default : $value;
        } else {
            $value = $value === null ? $default : $value;
        }

        if (self::$wrapReturn === true && !($value instanceof AeType)) {
            return self::wrap($value);
        }

        if (self::$wrapReturn === null)
        {
            if (is_scalar($value) || $value instanceof AeScalar) {
                return AeScalar::_wrapReturn($value);
            }

            if (is_array($value) || $value instanceof AeArray) {
                return AeArray::_wrapReturn($value);
            }
        }

        if (self::$wrapReturn === false && $value instanceof AeType) {
            $value = $value->getValue();
        }

        return $value;
    }
}

/**
 * Type exception class
 *
 * Type-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeTypeException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Type');
        parent::__construct($message, $code);
    }
}
?>