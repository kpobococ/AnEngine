<?php
/**
 * Event class file
 *
 * See {@link AeEvent} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Event class
 *
 * This class is a basic event and a global event dispatcher for the whole
 * framework.
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
    private $___stopPropagation = false;

    /**
     * Prevent default flag
     * @var bool
     */
    private $___preventDefault  = false;

    /**
     * Event constructor
     *
     * @param string $name   event name
     * @param object $target event target
     */
    public function __construct($name, AeObject $target = null)
    {
        $this->_name   = strtolower((string) $name);
        $this->_target = $target;
    }

    /**
     * Prevent default action
     *
     * If this method is called on the event inside the event listener method,
     * and the target object's class supports it, the default event action on
     * that object will not occur.
     *
     * @see AeEvent::stopPropagation(), AeEvent::stop()
     *
     * @return AeEvent self
     */
    public function preventDefault()
    {
        $this->___preventDefault = true;

        return $this;
    }

    /**
     * Stop subsequent events
     *
     * If this method is called on the event inside the event handler, any
     * remaining event handlers in target's event listener queue will not be run.
     *
     * @see AeEvent::preventDefault(), AeEvent::stop()
     *
     * @return AeEvent self
     */
    public function stopPropagation()
    {
        $this->___stopPropagation = true;

        return $this;
    }

    /**
     * Stop all
     *
     * This method is a shortcut for both {@link AeEvent::preventDefault()} and
     * {@link AeEvent::stopPropagation()} methods
     *
     * @uses AeEvent::preventDefault() to prevent default action
     * @uses AeEvent::stopPropagation() to stop event propagation
     *
     * @return AeEvent self
     */
    public function stop()
    {
        return $this->preventDefault()->stopPropagation();
    }

    /**
     * Get prevent default
     *
     * Returns true if the prevent default action flag has been set, false
     * otherwise. If the prevent default action flag is set, the {@link
     * AeObject::fireEvent()} method will return false. If there was any default
     * action defined for the event, it will be cancelled.
     *
     * <b>NOTE:</b> utilization of this flag depends solely on class developer
     *
     * @see AeEvent::_getStopPropagation()
     *
     * @return bool
     */
    protected function _getPreventDefault()
    {
        return $this->___preventDefault;
    }

    /**
     * Get stop propagation
     *
     * Returns true if the stop event propagation flag has been set, false
     * otherwise. If the stop event propagation action flag is set, any
     * remaining event handlers in event target's listener queue will not be
     * called. Utilization of this flag is implemented by {@link
     * AeObject::fireEvent()} method.
     *
     * @see AeEvent::_getPreventDefault()
     *
     * @return bool
     */
    protected function _getStopPropagation()
    {
        return $this->___stopPropagation;
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