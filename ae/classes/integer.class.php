<?php
/**
 * Integer class file
 *
 * See {@link AeInteger} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

if (!defined('PHP_INT_MIN')) {
    /**
     * The minimum integer value
     *
     * This value is not available in the PHP core, so we define it ourselves.
     * However, you are advised to use the {@link AeInteger::MIN} constant
     * instead of this one
     */
    define('PHP_INT_MIN', PHP_INT_MAX * -1 - 1);
}

/**
 * Integer class
 *
 * This class is a replacement for php's generic integer type. Made for
 * type-hinting and OOP-styled function call purposes.
 *
 * @method int getValue() getValue($default = null) Get a scalar integer value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeInteger extends AeNumeric
{
    /**
     * The minimum available integer value
     */
    const MIN = PHP_INT_MIN;

    /**
     * The maximum available integer value
     */
    const MAX = PHP_INT_MAX;

    /**
     * Scalar integer value
     * @var int
     */
    protected $_value;

    /**
     * Integer constructor
     *
     * @throws AeIntegerException #400 if the value passed is not an integer
     *
     * @param int $value
     */
    public function __construct($value = null)
    {
        if (!is_null($value) && !$this->setValue($value)) {
            throw new AeIntegerException('Invalid value passed: expecting null or int, ' . gettype($value) . ' given', 400);
        }
    }

    /**
     * Set an integer value
     *
     * If the value passed is not an integer (float or a numeric string), it
     * will be converted to integer. If the value is out of the integer bounds,
     * false is returned
     *
     * @todo return self
     * @todo throw an exception on invalid value
     *
     * @uses AeInteger::MIN
     * @uses AeInteger::MAX
     *
     * @param int $value
     *
     * @return bool true on valid value, false otherwise.
     */
    public function setValue($value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            return false;
        }

        $value = (int) $value;

        if (!is_int($value)) {
            return false;
        }

        $this->_value = $value;

        return true;
    }
}

/**
 * Integer exception class
 *
 * Integer-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeIntegerException extends AeNumericException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Integer');
        parent::__construct($message, $code);
    }
}
?>