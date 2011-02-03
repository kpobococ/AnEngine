<?php

class AeCallback extends AeObject
{
    protected $_value;
    protected $_arguments = null;

    public function __construct($callback = null, $method = null, $arguments = null)
    {
        $args = func_get_args();

        call_user_func_array(array($this, 'setValue'), $args);
    }

    public function setValue($callback, $method = null, $arguments = null)
    {
        $callback = AeType::unwrap($callback);
        $method   = AeType::unwrap($method);

        /*
         * Possible cases:
         * - callback is a function name
         * - callback is a class name + method
         * - callback is an object + method
         * - callback is an array
         */

        if (is_callable($callback)) {
            // *** Either function name, callback array or anonymous function
            $value = $callback;
            $anum  = 1;
        } else if (is_callable(array($callback, $method))) {
            // *** Either class and method names or object and method name
            $value = array($callback, $method);
            $anum  = 2;
        } else {
            throw new InvalidArgumentException('Expecting callback, ' . AeType::of($callback) . ' given', 400);
        }

        $this->_value = $value;

        if (func_num_args() > $anum) {
            $args = func_get_args();
            $args = array_slice($args, $anum);
            $this->setArguments($args);
        }

        return $this;
    }

    public function setArguments($arguments)
    {
        $num = func_num_args();

        if ($num == 1) {
            $arguments = (array) AeType::unwrap($arguments);
        } else {
            $arguments = func_get_args();
        }

        $this->_arguments = $arguments;

        return $this;
    }

    public function call($args = null)
    {
        if (AeType::of($args) == 'string' && $this->hasMethod($name = (string) $args)) {
            $args = func_get_arg(1);
            return parent::call($name, $args);
        }

        $callback = $this->value;

        if ($callback === null) {
            throw new UnexpectedValueException('Callback is not callable', 400);
        }

        $args = (array) AeType::unwrap($args);

        if (is_array($args)) {
            return call_user_func_array($callback, $args);
        } else if ($args === null && count($this->_arguments) > 0) {
            return call_user_func_array($callback, $this->_arguments);
        }

        return call_user_func($callback);
    }

    public function __invoke()
    {
        $args = func_num_args() > 0 ? func_get_args() : null;

        return $this->call($args);
    }
}