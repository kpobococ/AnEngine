<?php
/**
 * Date class file
 *
 * See {@link AeDate} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Date class
 *
 * This class enables you to work with dates as if they were a separate data
 * type. The Date class package also includes separate classes for working with
 * time intervals and time zones using {@link AeDate_Interval} and {@link
 * AeDate_Timezone} classes.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeDate extends AeObject
{
    /**
     * Atom feed date format
     *
     * Same as {@link AeDate::RFC3339 RFC3339} and {@link AeDate::W3C W3C}
     * formats:
     * <pre>2005-08-15T15:52:01+00:00</pre>
     */
    const ATOM = 'Y-m-d\TH:i:sP';

    /**
     * HTTP Cookies date format
     *
     * Same as {@link AeDate::RFC850 RFC850} format:
     * <pre>Monday, 15-Aug-05 15:52:01 UTC</pre>
     */
    const COOKIE = 'l, d-M-y H:i:s T';

    /**
     * ISO-8601 date format
     *
     * <pre>2005-08-15T15:52:01+0000</pre>
     */
    const ISO8601 = 'Y-m-d\TH:i:sO';

    /**
     * RFC822 date format
     *
     * Same as {@link AeDate::RFC1036 RFC1036} format:
     * <pre>Mon, 15 Aug 05 15:52:01 +0000</pre>
     */
    const RFC822 = 'D, d M y H:i:s O';

    /**
     * RFC850 date format
     *
     * Same as {@link AeDate::COOKIE COOKIE} format:
     * <pre>Monday, 15-Aug-05 15:52:01 UTC</pre>
     */
    const RFC850 = 'l, d-M-y H:i:s T';

    /**
     * RFC1036 date format
     *
     * Same as {@link AeDate::RFC822 RFC822} format:
     * <pre>Mon, 15 Aug 05 15:52:01 +0000</pre>
     */
    const RFC1036 = 'D, d M y H:i:s O';

    /**
     * RFC1123 date format
     *
     * Same as {@link AeDate::RFC2822 RFC2822} and {@link AeDate::RSS RSS}
     * formats:
     * <pre>Mon, 15 Aug 2005 15:52:01 +0000</pre>
     */
    const RFC1123 = 'D, d M Y H:i:s O';

    /**
     * RFC2822 date format
     *
     * Same as {@link AeDate::RFC1123 RFC1123} and {@link AeDate::RSS RSS}
     * formats:
     * <pre>Mon, 15 Aug 2005 15:52:01 +0000</pre>
     */
    const RFC2822 = 'D, d M Y H:i:s O';

    /**
     * RFC3339 date format
     *
     * Same as {@link AeDate::ATOM ATOM} and {@link AeDate::W3C W3C} formats:
     * <pre>2005-08-15T15:52:01+00:00</pre>
     */
    const RFC3339 = 'Y-m-d\TH:i:sP';

    /**
     * RSS date format
     *
     * Same as {@link AeDate::RFC1123 RFC1123} and {@link AeDate::RFC2822
     * RFC2822} formats:
     * <pre>Mon, 15 Aug 2005 15:52:01 +0000</pre>
     */
    const RSS = 'D, d M Y H:i:s O';

    /**
     * World Wide Web Consortium date format
     * 
     * Same as {@link AeDate::ATOM ATOM} and {@link AeDate::RFC3339 RFC3339}
     * formats:
     * <pre>2005-08-15T15:52:01+00:00</pre>
     */
    const W3C = 'Y-m-d\TH:i:sP';

    /**
     * Date value
     * @var string
     */
    protected $_value;

    /**
     * Constructor
     *
     * @see AeDate::setValue()
     *
     * @param array|string|float|int $value date value
     */
    public function __construct($value = null)
    {
        $this->setValue($value);
    }

    /**
     * Set date value
     *
     * The supported values are either a unix timestamp, a string, accepted by
     * the {@link strtotime() strtotime()} PHP function or an array with the
     * following keys: year, month, day, hour, minute, second, timezone. If one
     * of the keys is not set, the current value is used. If none of them are
     * set (an array is empty), the current date and time is used:
     * <code> // The following three lines create date objects with the same value
     * $d1 = new AeDate(array('year' => 2000, 'month' => 12, 'day' => 31, 'hour' => 15, 'minute' => 0, 'second' => 0, 'timezone' => 'UTC'));
     * $d2 = new AeDate('2000-12-31 15:00:00 UTC');
     * $d3 = new AeDate(978274800); // Unix timestamp
     *
     * echo $d1 . "\n" . $d2 . "\n" . $d3;</code>
     *
     * The above will output the following:
     * <pre> Sun, 31 Dec 2000 15:00:00 +0000
     * Sun, 31 Dec 2000 15:00:00 +0000
     * Sun, 31 Dec 2000 17:00:00 +0200</pre>
     *
     * The third date is the same, but the timezone is set to local instead of
     * UTC. The array form of date may seem cumbersome at first, but consider
     * the following code:
     * <code> $d1 = new AeDate(array('timezone' => 'UTC'));
     * $d2 = new AeDate(date('Y-m-d H:i:s') . ' UTC');
     *
     * echo $d1->getValue() === $d2->getValue() ? 'equal' : 'not equal'; // equal</code>
     *
     * You can also pass null to set the date to current date and time.
     *
     * @throws AeDateException #400 on invalid value
     *
     * @param array|string|float|int $value
     *
     * @return AeDate self
     */
    public function setValue($value = null)
    {
        if ($value instanceof AeType) {
            $value = $value->getValue();
        }

        // *** No value given, use current time
        if (is_null($value)) {
            $value = time();
        }

        // *** Illegal value type
        if (!is_array($value) && !is_string($value) && !is_int($value) && !is_float($value)) {
            throw new AeDateException('Invalid value passed: expecting scalar, ' . AeType::of($value) . ' given', 400);
        }

        // *** Convert a numeric string into a number
        if (is_string($value) && is_numeric($value)) {
            // *** The correct type is set below
            $value = (float) $value;
        }

        // *** Cast to integer if float is inside an integer value range
        if (is_float($value) && $value <= AeInteger::MAX && $value >= AeInteger::MIN) {
            $value = (int) $value;
        }

        if (is_array($value))
        {
            foreach ($value as $k => $v)
            {
                if (!in_array((string) $k, array('year', 'month', 'day', 'hour', 'minute', 'second', 'timezone'))) {
                    unset($value[$k]);
                }
            }

            $_string = '';
            $_bits   = array();

            $_bits[]  = isset($value['year'])     ? $value['year']     : date('Y');
            $_bits[]  = isset($value['month'])    ? $value['month']    : date('m');
            $_bits[]  = isset($value['day'])      ? $value['day']      : date('d');

            $_string .= implode('-', $_bits) . ' ';
            $_bits    = array();

            $_bits[]  = isset($value['hour'])     ? $value['hour']     : date('H');
            $_bits[]  = isset($value['minute'])   ? $value['minute']   : date('i');
            $_bits[]  = isset($value['second'])   ? $value['second']   : date('s');

            $_string .= implode(':', $_bits) . ' ';
            $_string .= isset($value['timezone']) ? str_replace(' ', '_', $value['timezone']) : date('e');

            $value    = $_string;

            unset($_string, $_bits);
        }

        if (!is_string($value)) {
            $this->_value = date('Y-m-d H:i:s e', $value);
        } else {
            $this->_value = date_create($value)->format('Y-m-d H:i:s e');
        }

        return $this;
    }

    /**
     * Get date value
     *
     * Return the date value. The value is returned in the RFC 2822 format. You
     * can specify another format using an optional <var>$format</var>
     * parameter. It uses the same formatting as PHP's {@link http://php.net/date
     * date()} function.
     *
     * @param string $format the format string, accepted by {@link date() date()}
     *                       PHP function
     *
     * @return string
     */
    public function getValue($format = AeDate::RFC2822)
    {
        return date_create($this->_value)->format($format);
    }

    /**
     * Add interval
     *
     * Adds a time interval to the date and returns the new date object
     *
     * @param AeDate_Interval|array|string $interval
     *
     * @return AeDate
     */
    public function add($interval)
    {
        if (!($interval instanceof AeDate_Interval)) {
            $interval = AeDate::interval($interval);
        }

        $vals = $interval->getValue();
        $date = $this->getValue('Y.m.d.H.i.s.P');

        list($y, $m, $d, $h, $i, $s, $p) = explode('.', $date);

        $s += $vals['seconds'];
        $i += $vals['minutes'];
        $h += $vals['hours'];
        // *** Do not add days here
        $m += $vals['months'];
        $y += $vals['years'];

        if ($s > 59) {
            $i += floor($s / 60);
            $s %= 60;
        }

        if ($i > 59) {
            $h += floor($i / 60);
            $i %= 60;
        }

        if ($h > 23) {
            // *** Do not modify the current day
            $vals['days'] += floor($h / 24);
            $h            %= 24;
        }

        if ($m > 11) {
            $y += floor($m / 12);
            $m %= 12;
        }

        $value = str_pad($y, 4, '0', STR_PAD_LEFT) . '-'
               . str_pad($m, 2, '0', STR_PAD_LEFT) . '-'
               . str_pad($d, 2, '0', STR_PAD_LEFT) . ' '
               . str_pad($h, 2, '0', STR_PAD_LEFT) . ':'
               . str_pad($i, 2, '0', STR_PAD_LEFT) . ':'
               . str_pad($s, 2, '0', STR_PAD_LEFT) . ' '
               // *** Add days here
               . $p . ' +' . $vals['days'] . ' days';

        return new AeDate($value);
    }

    /**
     * Subtract interval
     *
     * Subtracts a time interval from the date and returns the new date object
     *
     * @param AeDate_Interval|array|string $interval
     * 
     * @return AeDate
     */
    public function subtract($interval)
    {
        if (!($interval instanceof AeDate_Interval)) {
            $interval = AeDate::interval($interval);
        }

        $vals = $interval->getValue();
        $date = $this->getValue('Y.m.d.H.i.s.P');

        list($y, $m, $d, $h, $i, $s, $p) = explode('.', $date);

        if ($s < $vals['seconds']) {
            $vals['minutes'] += 1;
            $s               += 60;
        }

        $s -= $vals['seconds'];

        if ($i < $vals['minutes']) {
            $vals['hours'] += 1;
            $i             += 60;
        }

        $i -= $vals['minutes'];

        if ($h < $vals['hours']) {
            $vals['days'] += 1;
            $h            += 24;
        }

        $h -= $vals['hours'];

        if ($m < $vals['months']) {
            $vals['years'] += 1;
            $m             += 12;
        }

        // *** Do not subtract days here

        $m -= $vals['months'];
        $y -= $vals['years'];

        $value = str_pad($y, 4, '0', STR_PAD_LEFT) . '-'
               . str_pad($m, 2, '0', STR_PAD_LEFT) . '-'
               . str_pad($d, 2, '0', STR_PAD_LEFT) . ' '
               . str_pad($h, 2, '0', STR_PAD_LEFT) . ':'
               . str_pad($i, 2, '0', STR_PAD_LEFT) . ':'
               . str_pad($s, 2, '0', STR_PAD_LEFT) . ' '
               // *** Subtract days here
               . $p . ' -' . $vals['days'] . ' days';

        return new AeDate($value);
    }

    /**
     * Compare dates
     *
     * Compares the date to another date. This method returns -1, if the date is
     * less than the date value passed, 0, if they are equal and 1, if the date
     * is greater than the date value passed.
     *
     * If you specify the second optional <var>$operator</var> argument, you can
     * test for a particular relationship. The possible operators are: <, lt,
     * <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively. When using the
     * <var>$operator</var> argument, this method returns TRUE if the
     * relationship is the one specified by the operator, FALSE otherwise.
     *
     * @param AeDate|array|string|float|integer $value
     * @param string                            $operator
     *
     * @return int|bool
     */
    public function compare($value = null, $operator = null)
    {
        if (!($value instanceof AeDate)) {
            $value = new AeDate($value);
        }

        $zone  = new AeDate_Timezone('UTC');
        $date1 = $this->setTimezone($zone)->getValue('Y.m.d.H.i.s');
        $date2 = $value->setTimezone($zone)->getValue('Y.m.d.H.i.s');

        if ($operator !== null)
        {
            if ($operator instanceof AeString) {
                $operator = $operator->getValue();
            }

            $operator = strtolower($operator);

            return version_compare($date1, $date2, $operator);
        }

        return version_compare($date1, $date2);
    }

    /**
     * Get difference
     *
     * Returns an interval between two dates. An interval will have the number
     * of years, months, days, hours, minutes and seconds, which you can add to
     * the smaller of two date values to get the larger one. This also means,
     * that an interval is always positive. Use the {@link AeDate::compare()
     * compare()} method to detect the smaller of two date values
     *
     * @param AeDate|array|string|float|integer $value
     *
     * @return AeDate_Interval
     */
    public function difference($value = null)
    {
        if (!($value instanceof AeDate)) {
            $value = new AeDate($value);
        }

        switch ($this->compare($value))
        {
            case -1: {
                $date1 = $value;
                $date2 = $this;
            } break;

            case 1: {
                $date1 = $this;
                $date2 = $value;
            } break;

            case 0: {
                return new AeDate_Interval;
            } break;
        }

        $zone = new AeDate_Timezone('UTC');

        list($y1, $m1, $d1, $h1, $i1, $s1) = explode('.', $date1->setTimezone($zone)->getValue('Y.m.d.H.i.s'));
        list($y2, $m2, $d2, $h2, $i2, $s2) = explode('.', $date2->setTimezone($zone)->getValue('Y.m.d.H.i.s'));

        $interval = array();

        if ($s1 < $s2) {
            $s1 += 60;
            $i1 -= 1;
        }

        if ($i1 < $i2) {
            $i1 += 60;
            $h1 -= 1;
        }

        if ($h1 < $h2) {
            $h1 += 24;
            $d1 -= 1;
        }

        if ($d1 < $d2) {
            $temp  = new DateTime($y1 . '-' . $m1 . '-01 00:00:00 -1 day');
            $temp  = $temp->format('d');

            $d1 += (int) $temp;
            $m1 -= 1;
        }

        if ($m1 < $m2) {
            $m1 += 12;
            $y1 -= 1;
        }

        $interval['seconds'] = $s1 - $s2;
        $interval['minutes'] = $i1 - $i2;
        $interval['hours']   = $h1 - $h2;
        $interval['days']    = $d1 - $d2;
        $interval['months']  = $m1 - $m2;
        $interval['years']   = $y1 - $y2;

        return new AeDate_Interval($interval);
    }

    /**
     * Get timezone
     *
     * Returns a timezone for the date. If the timezone name is not defined
     * (i.e., the actual offset is used), it is autodetected, using the date's
     * offset and dst parameters for a reference. The autodetected timezone may
     * not be the most accurate one available, but will correctly reflect the
     * required offset
     *
     * @return AeDate_Timezone
     */
    public function getTimezone()
    {
        $zone = new AeDate_Timezone;

        try {
            $zone->setValue($this->getValue('e'));
        } catch (AeDateTimezoneException $e) {
            if ($e->getCode() !== 413) {
                throw $e;
            }

            $dst    = (bool) $this->getValue('I');
            $offset = (int) $this->getValue('Z');
            $abbrs  = timezone_abbreviations_list();

            foreach ($abbrs as $abbr => $data)
            {
                $row = $data[0];

                if ($row['dst'] !== $dst || $row['offset'] !== $offset) {
                    continue;
                }

                $zone->setValue(strtoupper($abbr));
                break;
            }
        }

        return $zone;
    }

    /**
     * Set timezone
     *
     * Returns a date with a different timezone set and the date's time properly
     * adjusted. This is useful to convert a date to be displayed for a user
     * with a different timezone setting.
     *
     * See {@link AeDate_Timezone::setValue()} method for a detailed overview of
     * accepted values
     *
     * @param AeDate_Timezone|string $value
     *
     * @return AeDate
     */
    public function setTimezone($value = null)
    {
        if (!($value instanceof AeDate_Timezone)) {
            $value = new AeDate_Timezone($value);
        }

        $zone = $value->getValue();

        if (strpos($zone, ' ') !== false) {
            $zone = str_replace(' ', '_', $zone);
        }

        $zone = @timezone_open($zone);
        $date = new DateTime($this->getValue());

        $date->setTimezone($zone);

        return new AeDate($date->format(self::W3C));
    }

    /**
     * Get current date
     *
     * Returns current date value wrapped in {@link AeDate} class instance
     *
     * @return AeDate
     */
    public static function now()
    {
        return new AeDate(time());
    }

    /**
     * Create date interval
     *
     * Creates and returns a date interval, using <var>$value</var> as an
     * interval value. See {@link AeDate_Interval::__construct()} for details on
     * accepted argument values.
     *
     * @param string|array $value
     *
     * @return AeDate_Interval
     */
    public static function interval($value)
    {
        return new AeDate_Interval($value);
    }

    /**
     * Create date timezone
     *
     * Creates and returns a date timezone, using <var>$value</var> as a
     * timezone value. See {@link AeDate_Timezone::__construct()} for details on
     * accepted argument values.
     *
     * @param string $value
     *
     * @return AeDate_Timezone
     */
    public static function timezone($value)
    {
        return new AeDate_Timezone($value);
    }

    /**
     * Cast to string
     *
     * Return a string value wrapped in {@link AeString} class instance
     * 
     * @param string $format the format string, accepted by the {@link
     *                       http://php.net/date() date()} PHP function
     *
     * @return AeString
     */
    public function toString($format = AeDate::RFC2822)
    {
        return new AeString($this->getValue($format));
    }

    /**
     * Cast to integer
     *
     * Return a unix timestamp value wrapped in {@link AeInteger} class
     * instance. Note, that if the value is out of an integer bounds, the value
     * wrapped will be the maximum (or minimum) integer value.
     *
     * <b>NOTE:</b> Using this method with date values out of the unix timestamp
     * bounds may lead to unexpected results.
     *
     * @return AeInteger
     */
    public function toInteger()
    {
        return new AeInteger((int) date_create($this->_value)->format('U'));
    }

    /**
     * Cast to float
     *
     * Return a unix timestamp value wrapped in {@link AeFloat} class instance.
     * Note, that if the value is out of a float bounds, the value wrapped will
     * be the maximum (or minimum) float value.
     *
     * <b>NOTE:</b> Using this method with date values out of the unix timestamp
     * bounds may lead to unexpected results.
     *
     * @return AeFloat
     */
    public function toFloat()
    {
        return new AeFloat((float) date_create($this->_value)->format('U'));
    }

    /**
     * Cast to array
     *
     * Returns an array value wrapped in {@link AeArray} class instance. The
     * array contains 7 following keys with their respective values: year, month,
     * day, hour, minute, second, timezone:
     * <code> $date = new AeDate('2000-05-15 10:20:30 Europe/London');
     * print_r($date->toArray()->getValue());</code>
     * The above code will result in the following:
     * <pre> Array
     * (
     *     [year] => 2000
     *     [month] => 5
     *     [day] => 15
     *     [hour] => 10
     *     [minute] => 20
     *     [second] => 30
     *     [timezone] => Europe/London
     * )</pre>
     *
     * @return AeArray
     */
    public function toArray()
    {
        $value = explode('.', $this->getValue('Y.m.d.H.i.s'));

        return new AeArray(array(
            'year'     => (int) $value[0],
            'month'    => (int) $value[1],
            'day'      => (int) $value[2],
            'hour'     => (int) $value[3],
            'minute'   => (int) $value[4],
            'second'   => (int) $value[5],
            'timezone' => (string) $this->getTimezone()->getValue()
        ));
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
 * Date exception class
 *
 * Date-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDateException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Date');
        parent::__construct($message, $code);
    }
}
?>