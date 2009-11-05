<?php
/**
 * Float class file
 *
 * See {@link AeFloat} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Float class
 *
 * This class is a replacement for php's generic float type. Made for
 * type-hinting and OOP-styled function call purposes.
 *
 * @method float getValue() getValue($default = null) Get a scalar float value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeFloat extends AeNumeric
{
    /**
     * Scalar float value
     * @var float
     */
    protected $_value;

    /**
     * Float constructor
     *
     * @throws AeFloatException #400 if the value passed is not a float
     *
     * @param float $value
     */
    public function __construct($value = null)
    {
        if (!is_null($value) && !$this->setValue($value)) {
            throw new AeFloatException('Invalid value passed: expecting null or float, ' . gettype($value) . ' given', 400);
        }
    }

    /**
     * Set a float value
     *
     * @todo return self
     * @todo throw an exception on invalid value
     *
     * @param float $value
     *
     * @return bool true on valid value, false otherwise.
     */
    public function setValue($value)
    {
        $value = (float) $value;

        if (!is_float($value)) {
            return false;
        }

        $this->_value = $value;

        return true;
    }

    /**
     * Round the value
     *
     * @see round()
     *
     * @param int|AeInteger $precision
     *
     * @return AeFloat
     */
    public function round($precision = 0)
    {
        if ($precision instanceof AeScalar) {
            $precision = $precision->toInteger()->getValue();
        }

        return new AeFloat(round($this->getValue(), $precision));
    }

    /**
     * Round fractions down
     *
     * @see floor()
     *
     * @return AeFloat
     */
    public function floor()
    {
        return new AeFloat(floor($this->getValue()));
    }

    /**
     * Round fractions up
     *
     * @see ceil()
     *
     * @return AeFloat
     */
    public function ceil()
    {
        return new AeFloat(ceil($this->getValue()));
    }

    /**
     * Format a number with grouped thousands
     *
     * @see number_format()
     *
     * @param int|AeInteger   $decimals      the number of decimal points
     * @param string|AeString $dec_point     the separator for the decimal point
     * @param string|AeString $thousands_sep the thousands separator
     *
     * @return AeString
     */
    public function format($decimals = 0, $dec_point = '.', $thousands_sep = ',')
    {
        if ($decimals instanceof AeScalar) {
            $decimals = $decimals->toInteger()->getValue();
        }

        if ($dec_point instanceof AeScalar) {
            $dec_point = $dec_point->toString()->getValue();
        }

        if ($thousands_sep instanceof AeScalar) {
            $thousands_sep = $thousands_sep->toString()->charAt(0, ',')->getValue();
        }

        return new AeString(number_format($this->getValue(), $decimals, $dec_point, $thousands_sep));
    }
}

/**
 * Float exception class
 *
 * Float-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeFloatException extends AeNumericException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Float');
        parent::__construct($message, $code);
    }
}
?>