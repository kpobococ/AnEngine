<?php
/**
 * Date timezone class file
 *
 * See {@link AeDate_Timezone} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Date timezone class
 *
 * This class enables you to work with date timezones as if they were a separate
 * data type. This class is used when changing or converting {@link AeDate
 * AeDate's} timezones. See {@link AeDate::setTimezone()}, {@link
 * AeDate::getTimezone()} methods
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeDate_Timezone extends AeObject
{
    /**
     * Timezone value
     * @var DateTimeZone
     */
    protected $_value;

    /**
     * Constructor
     *
     * If no value is passed, current system timezone is created.
     *
     * See the {@link AeDate_Timezone::setValue() setValue()} method
     * documentation for more details on accepted values
     *
     * @param string|DateTimeZone $value
     */
    public function __construct($value = null)
    {
        if (is_null($value)) {
            $value = date('e');
        }

        $this->setValue($value);
    }

    /**
     * Set timezone
     *
     * Sets the timezone value. This method accepts timezone names as strings.
     * You can get the full list of accepted values in the PHP manual: {@link
     * http://php.net/manual/en/timezones.php} (note, some timezones inside the
     * Others are not accepted):
     * <code> $tz   = new AeDate_Timezone('Europe/Amsterdam');
     * $date = new AeDate('2009-05-12 14:00:00', 'Europe/Moscow');
     *
     * echo $date; // Tue, 12 May 2009 14:00:00 +0400
     * echo $date->setTimezone($tz); // Tue, 12 May 2009 12:00:00 +0200</code>
     *
     * @throws AeDateTimezoneException #400 on invalid value
     * @throws AeDateTimezoneException #413 on unrecognized timezone identifier
     *
     * @param string|DateTimeZone $value
     *
     * @return AeDate_Timezone self
     */
    public function setValue($value)
    {
        if ($value instanceof AeString) {
            $value = $value->getValue();
        }

        if (!is_string($value) && !($value instanceof DateTimeZone)) {
            throw new AeDateTimezoneException('Invalid value passed: expecting string or DateTimeZone, ' . AeType::of($value) . ' given', 400);
        }

        if (!($value instanceof DateTimeZone))
        {
            if (strpos($value, ' ') !== false) {
                $value = str_replace(' ', '_', trim($value));
            }

            $zone = @timezone_open($value);

            if (!$zone) {
                throw new AeDateTimezoneException('Unrecognized timezone identifier: ' . $value, 413);
            }

            $value = $zone;
        }

        $this->_value = $value;

        return $this;
    }

    /**
     * Get timezone value
     *
     * Returns a string with one of the timezone names. Note, that this method
     * automatically replaces underscores with spaces: America/Los_Angeles
     * becomes America/Los Angeles. These values are still accepted by the {@link
     * AeDate_Timezone::setValue() setValue()} method, but may not be accepted
     * by built-in PHP functions and methods
     *
     * @return string
     */
    public function getValue()
    {
        return str_replace('_', ' ', $this->_value->getName());
    }

    /**
     * Cast to string
     *
     * Return a string value wrapped in {@link AeString} class instance
     *
     * @return AeString
     */
    public function toString()
    {
        return new AeString($this->getValue());
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
        return $this->getValue();
    }
}

/**
 * Date timezone exception class
 *
 * Date timezone-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDateTimezoneException extends AeDateException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Timezone');
        parent::__construct($message, $code);
    }
}
?>