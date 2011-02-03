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
     * @see AeCallback::setValue()
     *
     * @param object|string|array|null $callback
     * @param string                   $method
     */
    public function __construct($callback = null, $method = null, $arguments = null)
    {
        if (!is_null($callback)) {
            $this->setValue($callback, $method);
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
     * <b>NOTE:</b> If an instance of AeString is passed as the first parameter,
     * a second parameter is present and there is a method of an AeString
     * instance by that name, that instance will be treated as an object rather
     * than a class name:
     * <code> class Test
     * {
     *     public static function getValue()
     *     {
     *         return 'Test::getValue()';
     *     }
     * }
     *
     * $className = new AeString('Test');
     *
     * $c = new AeCallback($className, 'getValue');
     * echo $c->call(); // this prints "Test" instead of "Test::getValue()"
     *
     * // You can use type casting as a workaround:
     * $c = new AeCallback((string) $className, 'getValue');
     * echo $c->call(); // this prints "Test::getValue()"</code>
     *
     * @throws AeCallbackException #400 on invalid callback passed
     *
     * @param object|string|array $callback
     * @param string|null         $method
     *
     * @return bool true on valid value, false otherwise.
     */
    public function setValue($callback, $method = null)
    {
        if ($method instanceof AeType) {
            $method = $method->getValue();
        }

        if ($callback instanceof AeType)
        {
            // *** Check for a special string case
            if ($method === null || !($callback instanceof AeString) || !$callback->methodExists($method)) {
                $callback = $callback->getValue();
            }
        }

        if (is_string($callback))
        {
            if (is_string($method)) {
                // *** This is a static class method call
                $value = array($callback, $method);
            } else {
                // *** This is a function call
                $value = $callback;
            }
        } else if (is_array($callback)) {
            // *** This is either a method call
            $value = $callback;
        } else if (is_object($callback)) {
            // *** This is an object method call
            $value = array($callback, $method);
        } else {
            // *** This is bullshit
            throw new AeCallbackException('Invalid callback passed: expecting object, array or string, ' . AeType::of($callback) . ' given', 400);
        }

        if (!is_callable($callback)) {
            throw new AeCallbackException('Invalid callback passed: callback is not callable', 400);
        }

        $this->_value = $callback;

        return $this;
    }

    /**
     * Set callback arguments
     *
     * This method allows you to provide a default set of parameters passed to
     * the callback. These parameters are used if no parameters are passed to
     * the {@link AeCallback::call()} method
     *
     * @param array $arguments
     *
     * @return AeCallback self
     */
    public function setArguments($arguments)
    {
        $this->_arguments = (array) $arguments;

        return $this;
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
    public function call($args = array(), $ma = array())
    {
        $type = AeType::of($args);

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
class AeCallbackException extends AeException
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