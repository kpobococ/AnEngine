<?php
/**
 * Time interval class file
 *
 * See {@link AeDate_Interval} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Time interval class
 *
 * This class enables you to work with time intervals as if they were a separate
 * data type. This class is used when adding to, subtracting from or calculating
 * difference between AeDate class instances. See {@link AeDate::add()}, {@link
 * AeDate::subtract()} and {@link AeDate::difference()} methods
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeDate_Interval extends AeObject
{
    /**
     * Interval value
     * @var array
     */
    protected $_value;

    /**
     * Constructor
     *
     * If no value is passed, a zero interval is created.
     *
     * @see AeDate_Interval::setValue()
     *
     * @param array|string $value
     */
    public function __construct($value = 0)
    {
        if (!is_null($value)) {
            $this->setValue($value);
        }
    }

    /**
     * Set interval
     *
     * Sets the interval value. This method accepts the following values:
     * - <b>DateInterval format</b> - the PHP's interval format, available since
     *                                PHP 5.3.0. The 'P1Y2M3DT4H5M6S' sets the
     *                                interval to 1 year, 2 months, 3 days, 4
     *                                hours, 5 minutes and 6 seconds
     * - <b>An array of values</b>  -
     *
     * The interval consists of two blocks, at least one should be present for
     * this method to accept the value passed. For the date use "P3D", "P3M",
     * "P3Y" or a combination of the three e.g. "P2M5D" (Y = Years, M = Months,
     * D = Days). <b>Must be year month day format</b> "P5Y", "P5M2D", "P5Y4D". For
     * the time use "T3H", "T3M", "T3S" or a combination of the three e.g.
     * "T5H20M" (H = Hours, M = Minutes, S = Seconds). For dateTime use
     * "P5D2M4YT5H20M". The digit before the letter (NOT P or T) can be any
     * amount:
     * <code> // 7 days
     * $foo = new AeDate_Interval('P7D');
     * $bar = new AeDate_Interval(array('days' => 7));
     *
     * // 72 hours
     * $foo = new AeDate_Interval('T72H');
     * $bar = new AeDate_Interval(array('hours' => 72));
     *
     * // 3 months, 3 minutes
     * $foo = new AeDate_Interval('P3MT3M');
     * $bar = new AeDate_Interval(array('months' => 3, 'minutes' => 3));
     *
     * // Empty interval (zero seconds)
     * $foo = new AeDate_Interval('T0S'); // Or you can just use '0'
     * $bar = new AeDate_Interval(array());</code>
     *
     * <b>NOTE:</b> the interval value passed is simplified and may return a
     * different string:
     * <code> // 50 hours
     * $foo = new AeDate_Interval('T50H');
     * echo $foo; // outputs 'P2DT2H': 2 days and 2 hours</code>
     * This simplification, however, does not affect the number of days, since
     * different months have different number of days, but the interval itself
     * does not reflect a date and there is no way to convert days into months
     * without knowing the actual date:
     * <code> // 30 days and 72 hours
     * $foo = new AeDate_Interval('P30DT72H');
     * echo $foo; // outputs 'P33D': 33 days</code>
     *
     * @throws AeDateIntervalException #400 on invalid value
     *
     * @param array|string $value
     *
     * @return AeDate_Interval self
     */
    public function setValue($value)
    {
        if ($value instanceof AeType) {
            $value = $value->getValue();
        }

        if ((is_numeric($value) && $value == 0) || (is_array($value) && empty($value))) {
            // *** All values are zero
            $this->_value = array(0, 0, 0, 0, 0, 0);

            return $this;
        }

        // *** Check DateInterval format
        if (is_string($value))
        {
            if (!preg_match('#^(?:P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)?)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$#', $value, $matches)) {
                throw new AeDateIntervalException('Invalid interval value: string value must match DateInterval format', 400);
            }

            if (count($matches) == 1) {
                return $this;
            }

            $this->_value = $this->_simplify(array(
                0 => (int) @$matches[1],
                1 => (int) @$matches[2],
                2 => (int) @$matches[3],
                3 => (int) @$matches[4],
                4 => (int) @$matches[5],
                5 => (int) @$matches[6]
            ));

            return $this;
        }

        if (is_array($value))
        {
            $this->_value = $this->_simplify(array(
                0 => (int) @$value['years'],
                1 => (int) @$value['months'],
                2 => (int) @$value['days'],
                3 => (int) @$value['hours'],
                4 => (int) @$value['minutes'],
                5 => (int) @$value['seconds']
            ));

            return $this;
        }

        throw new AeDateIntervalException('Invalid interval value: expecting string or array, ' . AeType::of($value) . ' given', 400);
    }

    /**
     * Simplify interval
     *
     * This method converts excess values into the exact ones:
     *  - If the number of seconds is greater than 59, each 60 seconds are
     *    converted into 1 minute
     *  - If the number of minutes is greater than 59, each 60 minutes are
     *    converted into 1 hour
     *  - If the number of hours is greater than 23, each 24 hours are converted
     *    into 1 day
     *  - If the number of months is greater than 11, each 12 months are
     *    converted into 1 year
     *
     * As you can see, the days are skipped. See the {@link
     * AeDate_Interval::setValue() setValue()} method documentation for a
     * detailed reasoning behind this.
     *
     * @param array $value
     *
     * @return array
     */
    protected function _simplify($value)
    {
        if ($value[5] > 59) {
            $value[4] = $value[4] + floor($value[5] / 60);
            $value[5] = $value[5] % 60;
        }

        if ($value[4] > 59) {
            $value[3] = $value[3] + floor($value[4] / 60);
            $value[4] = $value[4] % 60;
        }

        if ($value[3] > 23) {
            $value[2] = $value[2] + floor($value[3] / 24);
            $value[3] = $value[3] % 24;
        }

        if ($value[1] > 11) {
            $value[0] = $value[0] + floor($value[1] / 12);
            $value[1] = $value[1] % 12;
        }

        return $value;
    }

    /**
     * Get interval value
     *
     * Returns an array with six keys, each key corresponding to a time period
     * value:
     * <code> $foo = new AeDate_Interval('P20Y4M2DT12H30M20S');
     * print_r($foo->getValue());</code>
     * The above code will output the following:
     * <pre> Array
     * (
     *     [years] => 20
     *     [months] => 4
     *     [days] => 2
     *     [hours] => 12
     *     [minutes] => 30
     *     [seconds] => 20
     * )</pre>
     *
     * @return array
     */
    public function getValue()
    {
        return array_combine(array('years', 'months', 'days', 'hours', 'minutes', 'seconds'), $this->_value);
    }

    /**
     * Get interval value as array
     *
     * Returns an {@link AeArray} class instance with the same values that are
     * returned by the {@link AeDate_Interval::getValue() getValue()} method
     *
     * @return AeArray
     */
    public function toArray()
    {
        return new AeArray($this->getValue());
    }

    /**
     * Get interval value as string
     *
     * Returns an {@link AeString} class instance with the DateInterval
     * formatted string. Returns 'T0S' value for zero intervals
     *
     * @return AeString
     */
    public function toString()
    {
        $date = $time = '';

        if ($this->_value[0] || $this->_value[1] || $this->_value[2])
        {
            $date = 'P';

            if ($this->_value[0]) {
                $date .= $this->_value[0] . 'Y';
            }

            if ($this->_value[1]) {
                $date .= $this->_value[1] . 'M';
            }

            if ($this->_value[2]) {
                $date .= $this->_value[2] . 'D';
            }
        }

        if ($this->_value[3] || $this->_value[4] || $this->_value[5])
        {
            $time = 'T';

            if ($this->_value[3]) {
                $time .= $this->_value[3] . 'H';
            }

            if ($this->_value[4]) {
                $time .= $this->_value[4] . 'M';
            }

            if ($this->_value[5]) {
                $time .= $this->_value[5] . 'S';
            }
        }

        if ($date === $time && $date === '') {
            $date = 'T0S';
        }

        return new AeString($date . $time);
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
        return $this->toString()->__toString();
    }
}

/**
 * Date interval exception class
 *
 * Date interval-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDateIntervalException extends AeDateException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Interval');
        parent::__construct($message, $code);
    }
}
?>