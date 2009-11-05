<?php
/**
 * Event class file
 *
 * See {@link AeEvent} class documentation.
 *
 * @requires PHP 5.2.0
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
    throw new AeEventException('The AeEvent class requires PHP version 5.2.0 or later', 503);
}

/**
 * Event class
 *
 * This class is a basic event and a global event dispatcher for the whole
 * framework.
 *
 * @requires PHP 5.2.0
 *
 * @todo consider removing static functionality in favor of AeObject event
 *       handling methods
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeEvent extends AeObject
{
    /**
     * Event name
     * @var string
     */
    protected $_name;

    /**
     * Event target or null
     * @var AeObject
     */
    protected $_target = null;

    /**
     * Stop propagation flag
     * @var bool
     */
    private $_stopPropagation = false;

    /**
     * Prevent default flag
     * @var bool
     */
    private $_preventDefault  = false;

    /**
     * Global events array
     * @var array
     */
    protected static $_events = array();

    /**
     * Event constructor
     *
     * @param string $name   event name
     * @param object $target event target
     */
    public function __construct($name, AeObject $target = null)
    {
        $this->_name   = self::_getName($name);
        $this->_target = $target;
    }

    /**
     * Prevent default action
     *
     * If this method is called on the event inside the event listener method,
     * and the target object supports it, the default event action on that
     * object will not occur
     */
    public function preventDefault()
    {
        $this->_preventDefault = true;
    }

    /**
     * Stop subsequent events
     *
     * If this method is called on the event inside the event listener method,
     * any subsequent event listeners will not be run
     */
    public function stopPropagation()
    {
        $this->_stopPropagation = true;
    }

    /**
     * Stop all
     *
     * This method is a shortcut for both {@link AeEvent::preventDefault()} and
     * {@link AeEvent::stopPropagation()} methods
     */
    public function stop()
    {
        $this->preventDefault();
        $this->stopPropagation();
    }

    /**
     * Create event listener
     *
     * Creates and returns an event listener object, using <var>$callback</var>
     * as a callback value for the listener. See {@link AeEvent_Listener::__construct()}
     * for details on accepted argument values.
     *
     * @param AeCallback|array|string $value
     *
     * @return AeEvent_Listener
     */
    public static function listener($callback)
    {
        if ($callback instanceof AeEvent_Listener) {
            return $callback;
        }

        return new AeEvent_Listener($callback);
    }

    // *** Event dispatching API
    /**
     * Add event
     *
     * Registers an event listener for the event, identified by <var>$name</var>,
     * using <var>$listener</var> as the listener callback. The third parameter
     * defines that the event should be registered for a certain object instead
     * of it being registered globally.
     *
     * You can also pass an associative array of name/listener pairs to be
     * added, in which case the second argument should be null
     *
     * @see AeObject::addEvent()
     *
     * @param array|string            $name
     * @param AeCallback|array|string $listener
     * @param AeObject                $target
     *
     * @return AeEvent_Listener
     */
    public static function add($name, $listener, AeObject $target = null)
    {
        $type = AeType::of($name);

        if ($type == 'array')
        {
            $return = array();

            foreach ($type as $n => $l) {
                $return[$n] = self::add($n, $l, $target);
            }

            return $return;
        } else if ($type != 'string') {
            throw new AeEventException('Invalid name type: expecting array or string, ' . $type . ' given', 400);
        }

        $name     = self::_getName($name);
        $listener = self::listener($listener);

        self::_checkEvents($name, $target);

        if ($target !== null) {
            $target->___events[$name][] = $listener;
        } else {
            self::$_events[$name][] = $listener;
        }

        return $listener;
    }

    /**
     * Remove event
     *
     * Unregisteres an event listener for the event, identified by <var>$name</var>,
     * using <var>$listener</var> to identify the actual function to remove. The
     * third parameter defines that the event should be unregistered for a certain
     * object instead of it being unregistered globally.
     *
     * You can also pass an associative array of name/listener pairs to be
     * removed, in which case the second argument should be null
     *
     * @see AeObject::removeEvent()
     *
     * @param array|string            $name
     * @param AeCallback|array|string $listener
     * @param AeObject                $target
     *
     * @return AeEvent_Listener
     */
    public static function remove($name, $listener, AeObject $target = null)
    {
        $type = AeType::of($name);

        if ($type == 'array')
        {
            $return = array();

            foreach ($name as $n => $l) {
                $return[$n] = self::remove($n, $l, $target);
            }

            return $return;
        } else if ($type != 'string') {
            throw new AeEventException('Invalid name type: expecting array or string, ' . $type . ' given', 400);
        }

        $name   = self::_getName($name);
        $events = self::_getEvents($name, $target);

        if ($listener === null)
        {
            if ($target !== null) {
                unset($target->___events[$name]);
            } else {
                unset(self::$_events[$name]);
            }

            return $events;
        } else if (!($listener instanceof AeEvent_Listener)) {
            throw new AeEventException('Invalid listener type: expecting instance of AeEvent_Listener, ' . AeType::of($listener) . ' given', 400);
        }

        $hash = spl_object_hash($listener);

        foreach ($events as $i => $l)
        {
            $h = spl_object_hash($l);

            if ($hash === $h)
            {
                unset($events[$i]);

                $events = array_values($events);

                if ($target !== null) {
                    $target->___events[$name] = $events;
                } else {
                    self::$_events[$name] = $events;
                }

                return $l;
            }
        }

        return false;
    }

    /**
     * Fire event
     *
     * Triggers an event, identified by <var>$name</var>, using <var>$args</var>
     * as parameters for the event listener method. The third parameter defines
     * that the event should be fired for a certain object instead of it being
     * fired globally.
     *
     * The <var>$args</var> parameter can be a single parameter or an array of
     * parameters. If you want to pass an array as a single parameter, you can
     * either wrap it inside another array, or inside an instance of {@link AeArray}
     * class.
     *
     * This method returns a boolean value, indicating a prevent default flag
     * state: true, if flag was not set, false otherwise. The usage of this
     * return value depends solely on your application's architecture and your
     * choice
     *
     * @see AeObject::fireEvent()
     *
     * @param string   $name
     * @param mixed    $args
     * @param AeObject $target
     *
     * @return bool
     */
    public static function fire($name, $args = null, AeObject $target = null)
    {
        $type = AeType::of($name);

        if ($type != 'string') {
            throw new AeEventException('Invalid name type: expecting string, ' . $type . ' given', 400);
        }

        $name   = self::_getName($name);
        $events = self::_getEvents($name, $target);

        if (count($events) > 0)
        {
            $event = new AeEvent($name, $target);
            $args  = self::_getArgs($event, $args);

            foreach ($events as $listener)
            {
                $listener->call($args);

                if ($event->_stopPropagation) {
                    break;
                }
            }

            if ($event->_preventDefault) {
                return false;
            }
        }

        return true;
    }

    /**
     * Copy events
     *
     * Copies all events from one object to another. If the optional <var>$name</var>
     * parameter is set, only events, identified by that name, will be copied.
     * Otherwise, all the events will be copied.
     *
     * <b>NOTE:</b> If the target object has any events of its own, they will be
     * overwritten. If the optional <var>$name</var> parameter is not set, all
     * the events will be overwritten
     *
     * @param AeObject $from
     * @param AeObject $to
     * @param string   $name
     *
     * @return array an array of copied events
     */
    public static function copy(AeObject $from, AeObject $to, $name = null)
    {
        if ($name === null)
        {
            $events        = $from->___events;
            $to->___events = $events;

            return $events;
        }

        $name   = self::_getName($name);
        $events = self::_getEvents($name, $from);

        $to->___events[$name] = $events;

        return $events;
    }

    /**
     * Get clean name
     *
     * @param string $name
     *
     * @return string a cleaned version of event name
     */
    protected static function _getName($name)
    {
        return strtolower((string) $name);
    }

    /**
     * Get parameters
     *
     * Returns an array of listener callback method parameters, including event
     * object
     *
     * @param AeEvent $event
     * @param mixed   $args
     *
     * @return array
     */
    protected static function _getArgs(AeEvent $event, $args = null)
    {
        if ($args === null) {
            return array($event);
        }

        if (!is_array($args)) {
            return array($event, $args);
        }

        array_unshift($args, $event);

        return $args;
    }

    /**
     * Check event name
     *
     * @param string   $name
     * @param AeObject $target 
     */
    protected static function _checkEvents($name, AeObject $target = null)
    {
        if ($target !== null)
        {
            if (!is_array($target->___events)) {
                $target->___events = array();
            }

            if (!isset($target->___events[$name]) || !is_array($target->___events[$name])) {
                $target->___events[$name] = array();
            }
        } else {
            if (!is_array(self::$_events)) {
                self::$_events = array();
            }

            if (!isset(self::$_events[$name]) || !is_array(self::$_events[$name])) {
                self::$_events[$name] = array();
            }
        }
    }

    /**
     * Get events
     *
     * @param string   $name
     * @param AeObject $target
     *
     * @return array
     */
    protected static function _getEvents($name, AeObject $target = null)
    {
        self::_checkEvents($name, $target);

        if ($target !== null) {
            return $target->___events[$name];
        }

        return self::$_events[$name];
    }
}

/**
 * Event exception class
 *
 * Event-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeEventException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Event');
        parent::__construct($message, $code);
    }
}
?>