<?php
/**
 * Event listener class file
 *
 * See {@link AeEvent_Listener} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Event listener class
 *
 * This class is a callback class for event listener methods. During a listener
 * execution, it checks the return value and, if it's false, calls an {@link
 * AeEvent::stop()} method, cancelling all subsequent event listener executions
 * and stopping any default action on the event target, if the feature is
 * supported
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeEvent_Listener extends AeCallback
{
    /**
     * Constructor
     *
     * The accepted parameters are the same here as in the {@link AeCallback}
     * class constructor, except that it also accepts instances of {@link AeCallback}
     *
     * @param AeCallback|object|array|string $callback
     * @param string                         $method
     */
    public function __construct($callback = null, $method = null)
    {
        if ($callback instanceof AeCallback) {
            $callback = $callback->value;
            $method   = null;
        }

        parent::__construct($callback, $method);
    }

    /**
     * Call event listener method
     *
     * Calls an event listener method and handles return values.
     *
     * <b>NOTE:</b> For backwards compatibility with the {@link AeObject::call()}
     * method, a second parameter is introduced. If the <var>$args</var> parameter
     * is a string, it is used as the method name, and <var>$ma</var> is used
     * as an array of parameters for that method
     *
     * @see AeCallback::call(), AeObject::call()
     *
     * @param array|string $args an array of parameters
     * @param array        $ma
     *
     * @return bool|mixed
     */
    public function call($args, $ma = array())
    {
        if (AeType::of($args) == 'string') {
            return parent::call($args, $ma);
        }

        $return = parent::call($args);

        if ($return === false) {
            $event = $args[0];
            $event->stop();
        }

        return true;
    }
}

/**
 * Event listener exception class
 *
 * Event listener-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeEventListenerException extends AeEventException
{
    /**
     * Exception constructor
     *
     * @param string         $message
     * @param int            $code
     * @param AeEventSubject $subject
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Listener');
        parent::__construct($message, $code);
    }
}

?>