<?php
/**
 * Callback class file
 *
 * See {@link AeCallback} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * Callback class
 *
 * This class is a replacement for php's generic callback pseudo-type (seen in
 * their documentation). One of the reasons to have this kind of class - is to
 * be able to type-hint it for a method. Because PHP's type-hinting only
 * supports class names and arrays.
 *
 * @method string|array getValue() getValue($default = null) Get a scalar
 *                                                           callback value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeCallback extends AeObject
{
    /**
     * Scalar callback value
     * @var string|array
     */
    protected $_value;

    /**
     * Callback parameters
     * @var array
     */
    protected $_arguments = null;

    /**
     * Callback constructor
     *
     * Possible usages:
     *
     * <code>// Regular function
     * $cb = new AeCallback('myFunction');
     *
     * // Static method
     * $cb = new AeCallback('MyClass', 'myMethod');
     * $cb = new AeCallback(array('MyClass', 'myMethod'));
     *
     * // Object instance method
     * $cb = new AeCallback($class, 'myMethod');
     * $cb = new AeCallback(array($class, 'myMethod'));
     *
     * // Empty callback
     * $cb = new AeCallback();</code>
     *
     * @throws AeCallbackException #400 if an invalid callback value is passed
     *
     * @param object|string|array|null $callback
     * @param string                   $method
     */
    public function __construct($callback = null, $method = null, $arguments = null)
    {
        if (!is_null($callback) && !$this->setValue($callback, $method)) {
            throw new AeCallbackException('Invalid callback value passed', 400);
        }

        if (!is_null($arguments)) {
            $this->setArguments($arguments);
        }
    }

    /**
     * Set a callback value
     *
     * Set a new callback value. See {@link AeCallback::__construct()
     * constructor} for possible usages.
     *
     * @param object|string|array $callback
     * @param string|null         $method
     *
     * @return bool true on valid value, false otherwise.
     */
    public function setValue($callback, $method = null)
    {
        if (is_array($callback) || (is_string($callback) && !is_string($method)))
        {
            // *** Raw callback
            if (!is_callable($callback)) {
                return false;
            }

            $this->_value = $callback;
        } else if (is_string($method) && (is_object($callback) || is_string($callback))) {
            // *** Object or class
            if (!is_callable(array($callback, $method))) {
                return false;
            }

            $this->_value = array($callback, $method);
        } else {
            return false;
        }

        return true;
    }

    public function setArguments($arguments)
    {
        $this->_arguments = (array) $arguments;
    }

    /**
     * Call stored callback
     *
     * Call stored callback. This method accepts an infinite number of
     * parameters to be passed to the call.
     *
     * <b>NOTE:</b> For backwards compatibility with the {@link AeObject::call()}
     * method, a second parameter is introduced. If the <var>$args</var> parameter
     * is a string, it is used as the method name, and <var>$ma</var> is used
     * as an array of parameters for that method
     *
     * @see AeObject::call()
     *
     * @throws AeCallbackException #400 if no callback value is currently stored
     * @throws AeCallbackException #400 if arguments type is invalid
     *
     * @param array|string $args an array of parameters
     * @param array        $ma
     *
     * @return mixed callback call result
     */
    public function call($args = array(), $ma = null)
    {
        $type = AeType::typeOf($args);

        // *** Backwards compatibility with AeObject::call()
        if ($type == 'string' && $this->methodExists($name = (string) $args)) {
            return parent::call($name, $ma);
        }

        $callback = $this->getValue(false);

        if (!$callback) {
            throw new AeCallbackException('No callback value stored', 400);
        }

        if ($type != 'array') {
            throw new AeCallbackException('Invalid args type: expecting array, ' . $type . ' given', 400);
        }

        if (count($args) > 0) {
            return call_user_func_array($callback, $args);
        } else if (count($this->_arguments) > 0) {
            return call_user_func_array($callback, $this->_arguments);
        }

        return call_user_func($callback);
    }
}

/**
 * Callback exception class
 *
 * Callback-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeCallbackException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Callback');
        parent::__construct($message, $code);
    }
}
?>