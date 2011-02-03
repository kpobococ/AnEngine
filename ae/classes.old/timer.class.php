<?php
/**
 * Timer class file
 *
 * See {@link AeTimer} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Timer class
 *
 * This is a timer class, useful for loading- or execution-time measurements in
 * the framework. Its usage is very simple:
 *
 * <code> $timer = new AeTimer;
 *
 * // ... some code here
 * echo $timer; // will print time elapsed since timer start
 *
 * // ... some more code here
 * echo $timer; // will print time elapsed since timer start</code>
 *
 * See {@link AeTimer::__construct()} for more details.
 *
 * @method float getStart() getStart() Get timer start time with microseconds
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeTimer extends AeObject
{
    /**
     * Timer start time with microseconds
     * @var float
     */
    protected $_start;

    /**
     * Timer constructor
     *
     * @param float $start custom timer start time
     */
    public function __construct($start = null)
    {
        if ($start === null) {
            $start = self::microtime();
        }

        $this->setStart($start);
    }

    /**
     * Set timer start time
     *
     * @param float $start timer start time
     *
     * @return bool true if new start time set successfully, false otherwise
     */
    public function setStart($start)
    {
        if (!is_float($start)) {
            return false;
        }

        $this->_start = $start;

        return true;
    }

    /**
     * Get human timer value
     *
     * Return human-readable timer value since timer start. This method is used
     * if casting the object to string directly.
     *
     * @param int $decimals number of decimals to show
     *
     * @return string
     */
    public function toString($decimals = 6)
    {
        return number_format(self::microtime() - $this->getStart(), (int) $decimals, '.', '');
    }

    /**
     * Get current microtime
     *
     * Return current time in seconds with microseconds
     *
     * @return float
     */
    public static function microtime()
    {
        return microtime(true);
    }
}

/**
 * Timer exception class
 *
 * Timer-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeTimerException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Timer');
        parent::__construct($message, $code);
    }
}
?>