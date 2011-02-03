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
     * @see AeFloat::setValue()
     *
     * @param float $value
     */
    public function __construct($value = null)
    {
        if (!is_null($value)) {
            $this->setValue($value);
        }
    }

    /**
     * Set a float value
     *
     * @throws AeFloatException #400 on invalid value
     *
     * @param float $value
     *
     * @return AeFloat self
     */
    public function setValue($value)
    {
        $value = (float) $value;

        if (!is_float($value)) {
            throw new AeFloatException('Invalid value passed: expecting float, ' . AeType::of($value) . ' given', 400);
        }

        $this->_value = $value;

        return $this;
    }

    /**
     * Round the value
     *
     * @see round()
     *
     * @param int $precision
     *
     * @return AeFloat
     */
    public function round($precision = 0)
    {
        if ($precision instanceof AeScalar) {
            $precision = $precision->toInteger()->getValue();
        }

        return new AeFloat(round($this->_value, $precision));
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
        return new AeFloat(floor($this->_value));
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
        return new AeFloat(ceil($this->_value));
    }

    /**
     * Format a number with grouped thousands
     *
     * @see number_format()
     *
     * @param int    $decimals      the number of decimal points
     * @param string $dec_point     the separator for the decimal point
     * @param string $thousands_sep the thousands separator
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

        return new AeString(number_format($this->_value, $decimals, $dec_point, $thousands_sep));
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