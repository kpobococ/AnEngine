<?php
/**
 * Object class file
 *
 * See {@link AeObject} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Basic object class
 *
 * This class is a basic class for many other classes in AnEngine. It has
 * several basic methods and an automatic getter and setter methods
 * implementation. For example:
 *
 * <code> class Foo extends AeObject
 * {
 *     protected $_value;
 * }
 *
 * // we can now use a virtual getter and setter on our $_value property:
 * $foo = new Foo;
 *
 * echo $foo->getValue(); // camelCase, no underscores
 * $foo->setValue('bar');
 *
 * // this is also supported for virtual getters:
 * echo $foo->getValue('default'); // will return 'default' if $_value not set</code>
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeObject
{
    /**
     * Object event handlers
     * @var array
     */
    private $___events;

    /**
     * Generic property getter
     *
     * This method tries to get the value of a requested property. A property
     * getter method is called if found, otherwise a raw property value is
     * returned. Lets say we want to get a value for a property named "foo",
     * here's what this method does:
     *
     * - call getFoo() method if it exists;
     * - return $foo property value if it exists;
     * - return $_foo property value if it exists;
     * - return <var>$default</var> value if it is set;
     * - return null;
     *
     * Note, that direct calls to protected properties are handled via this
     * method (unless property getter is found), but direct calls to public
     * properties are not:
     *
     * <code> class T1 extends AeObject
     * {
     *      public $foo  = 'foo';
     *      public $_bar = 'bar';
     *
     *      protected $_baz = 'baz';
     * }
     *
     * $t = new T1;
     *
     * echo $t->foo;  // direct foo property value get
     * echo $t->bar;  // bar property value get via get('bar')
     * echo $t->_bar; // direct bar property value get
     *
     * echo $t->baz;  // baz property value get via get('baz')
     * echo $t->_baz; // same as $t->baz</code>
     *
     * @param string $name    name of the property (public or protected)
     * @param mixed  $default value to return if requested property not set
     *                        or not found
     * 
     * @return mixed property value, default value if property not set or not
     *               found
     */
    public function get($name, $default = null)
    {
        // *** Strip leading underscores
        $name = ltrim($name, '_');

        // *** Check if explicit getter is present for the property
        if ($this->_methodExists('get' . ucfirst($name))) {
            return $this->call('get' . ucfirst($name), $default);
        }

        // *** Check if property exists
        if ($this->_propertyExists($name)) {
            return is_null($this->$name) ? $default : $this->$name;
        }

        // *** Check if _property exists
        if ($this->_propertyExists('_' . $name)) {
            $_name = '_' . $name;
            return is_null($this->$_name) ? $default : $this->$_name;
        }

        return $default;
    }

    /**
     * Generic property setter
     *
     * This method tries to set the value of a requested property. A property
     * setter method is called if found, otherwise property value is
     * assigned directly. Lets say we want to set a value for a property named
     * "foo", here's what this method does:
     *
     * - call setFoo() method if it exists;
     * - set the value of the $foo property if it exists;
     * - set the value of the $_foo property if it exists;
     * - return false;
     *
     * Note, that direct calls to protected properties are handled via this
     * method (unless property setter is found), but direct calls to public
     * properties are not:
     *
     * <code>class T1 extends AeObject
     * {
     *      public $foo;
     *      public $_bar;
     *
     *      protected $_baz;
     * }
     *
     * $t = new T1;
     *
     * $t->foo  = 'foo'; // foo property value set directly
     * $t->bar  = 'bar'; // bar property value set via set('bar', 'bar')
     * $t->_bar = 'bar'; // bar property value set directly
     *
     * $t->baz  = 'baz'; // baz property value set via set('baz', 'baz')
     * $t->_baz = 'baz'; // same as $t->baz = 'baz'</code>
     *
     * @todo consider adding exception, if property does not exist
     *
     * @param string $name  name of the property (public or protected)
     * @param mixed  $value new value of the property
     *
     * @return AeObject self
     */
    public function set($name, $value)
    {
        // *** Strip leading underscores
        $name = ltrim($name, '_');

        // *** Check if explicit setter is present for the property
        if ($this->_methodExists('set' . ucfirst($name))) {
            return $this->call('set' . ucfirst($name), $value);
        }

        // *** Check if property exists
        if ($this->_propertyExists($name)) {
            $this->$name = $value;
            return $this;
        }

        // *** Check if _property exists
        if ($this->_propertyExists('_' . $name)) {
            $_name        = '_' . $name;
            $this->$_name = $value;
            //return $this;
        }

        return $this;
    }

    /**
     * Generic property unsetter
     *
     * This method tries to unset the value of a requested property. Uses {@link
     * AeObject::set()} to do the job, but returns the previous property value:
     * <code>class T1 extends AeObject
     * {
     *      protected $_foo;
     * }
     *
     * $t = new T1;
     *
     * $t->foo = 'bar';
     *
     * echo $t->clear($foo); // 'bar'</code>
     *
     * @param string $name  name of the property (public or protected)
     *
     * @return AeObject self
     */
    public function clear($name)
    {
        $value = null;

        if ($this->propertyExists($name, 'set')) {
            $this->set($name, null);
        }

        return $this;
    }

    /**
     * Check if property exists
     *
     * Checks if an object has property or a getter/setter for that property.
     * An optional <var>$mode</var> parameter can be used to specify if you
     * want to check for a property getter or a property setter:
     * <code> class Test extends AeObject
     * {
     *     protected $_foo;
     *     private $_baz;
     *
     *     public function getBar()
     *     {
     *         return 'bar';
     *     }
     *
     *     public function setBaz($value)
     *     {
     *         $this->_baz = $value;
     *     }
     * }
     *
     * $test = new Test;
     *
     * $test->propertyExists('foo'); // true
     * $test->propertyExists('bar'); // true
     * $test->propertyExists('baz'); // false
     *
     * $test->propertyExists('foo', 'set'); // true
     * $test->propertyExists('bar', 'set'); // false
     * $test->propertyExists('baz', 'set'); // true</code>
     *
     * @see AeObject::methodExists()
     *
     * @param string $name property name
     * @param string $mode property mode. Default: get
     *
     * @return bool
     */
    public function propertyExists($name, $mode = 'get')
    {
        // *** Strip leading underscores
        $name = ltrim($name, '_');
        $mode = in_array(strtolower($mode), array('get', 'set')) ? $mode : 'get';

        // *** Check if explicit getter/setter is present for the property
        if ($this->_methodExists($mode . ucfirst($name))) {
            return true;
        }

        // *** Check if property exists
        if ($this->_propertyExists($name)) {
            return true;
        }

        // *** Check if _property exists
        if ($this->_propertyExists('_' . $name)) {
            return true;
        }

        return false;
    }

    /**
     * Check if method exists
     *
     * Checks if an object has a method, either defined or virtual. This is
     * particularly useful when working with virtual getters and setters:
     * <code> class Test extends AeObject
     * {
     *     protected $_foo;
     * }
     *
     * $test = new Test;
     * 
     * $test->methodExists('getFoo'); // true
     * $test->methodExists('setFoo'); // true
     * </code>
     *
     * @see AeObject::propertyExists()
     *
     * @param string $name method name
     *
     * @return bool
     */
    public function methodExists($name)
    {
        // *** Strip leading underscores
        $name = ltrim($name, '_');

        if ($this->_methodExists($name)) {
            return true;
        }

        // *** Check if this is a getter or a setter
        $prefix = substr($name, 0, 3);

        if ($prefix == 'get' || $prefix == 'set')
        {
            $_name = strtolower($name[3]) . substr($name, 4);

            if ($this->_propertyExists($_name)) {
                return true;
            }

            if ($this->_propertyExists('_' . $_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return class name
     *
     * @return string current object's class name
     */
    public function getClass()
    {
        return get_class($this);
    }

    /**
     * Call method
     *
     * This method is a shorthand to calling a variable method via {@link
     * http://php.net/call_user_func_array call_user_func_array()}. It accepts
     * an array of arguments instead of a variable number of arguments.
     *
     * @param string $name name of the method to call
     * @param array  $args an array of arguments
     *
     * @return mixed method return value
     */
    public function call($name, $args = array())
    {
        // *** Strip leading underscores
        $name = ltrim($name, '_');
        $args = (array) $args;

        if ($this->_methodExists($name)) {
            return call_user_func_array(array($this, $name), $args);
        }

        return $this->__call($name, $args);
    }

    /**
     * Virtual property getter/setter support method
     *
     * This method is called every time an undefined method is called. If a
     * called method is formatted as a setter or getter method, {@link
     * AeObject::set()} or {@link AeObject::get()} is called respectively.
     *
     * @throws AeObjectException #404 if called method is not a valid getter or
     * setter method.
     *
     * @param string $name name of the method
     * @param array  $args method call arguments
     *
     * @return mixed {@link AeObject::get()} or {@link AeObject::set()} results
     */
    public function __call($name, $args)
    {
        $prefix   = substr($name, 0, 3);
        $property = false;

        // *** Is it a getter or a setter?
        if ($prefix == 'get' || $prefix == 'set') {
            $property = ltrim(substr($name, 3), '_');
            $property = strtolower($property[0]) . substr($property, 1);
        }

        if ($prefix == 'get' && $property)
        {
            // *** It's a property getter call
            if ($this->_propertyExists($property) || $this->_propertyExists('_' . $property))
            {
                if (isset($args[0])) {
                    return $this->get($property, $args[0]);
                }

                return $this->get($property);
            }
        }

        if ($prefix == 'set' && $property)
        {
            // *** It's a property setter call
            if ($this->_propertyExists($property) || $this->_propertyExists('_' . $property)) {
                return $this->set($property, $args[0]);
            }
        }

        throw new AeObjectException('Call to undefined method ' . $this->getClass() . '::' . $name . '()', 404);
    }

    /**
     * String type cast support method
     *
     * This method is called every time an object is being cast to string (i.e.
     * echoed). If a custom toString() method is defined for a type cast target,
     * it is called and its result is returned.
     *
     * @return string toString() method result or a custom object string
     */
    public function __toString()
    {
        if (method_exists($this, 'toString')) {
            return $this->toString();
        }

        return 'object(' . $this->getClass() . ')';
    }

    /**
     * Virtual property getter support method
     *
     * This method is called every time an undefined or protected property is
     * being directly accessed. It redirects all calls to {@link AeObject::
     * get()} method.
     *
     * @param string $name property name
     *
     * @return mixed property value or null if property not set or not found
     */
    public function __get($name)
    {
        return $this->get($name, null);
    }

    /**
     * Virtual property setter support method
     *
     * This method is called every time an undefined or protected property is
     * being directly set. It redirects all calls to {@link AeObject::set()}
     * method.
     *
     * @param string $name  property name
     * @param mixed  $value new property value
     *
     * @return bool true if property found and set, false otherwise
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Virtual property getter support method
     *
     * This method is called every time an {@link isset()} operation is
     * performed on an undefined or protected property.
     *
     * @param string $name property name
     *
     * @return bool true if property exists and is not null, false otherwise
     */
    public function __isset($name)
    {
        // *** Strip leading underscores
        $name = ltrim($name, '_');

        if ($this->propertyExists($name)) {
            return !is_null($this->get($name));
        }

        $name = '_' . $name;

        return property_exists($this, $name) && !is_null($this->$name);
    }

    /**
     * Virtual property setter support method
     *
     * This method is called every time an {@link unset()} operation is
     * performed on an undefined or protected property.
     *
     * @param string $name property name
     */
    public function __unset($name)
    {
        $this->clear($name);
    }

    /**
     * Check if property exists
     *
     * Checks if an actual property exists
     *
     * @param string $name property name
     *
     * @return bool
     */
    protected function _propertyExists($name)
    {
        return property_exists($this, $name);
    }

    /**
     * Check if method exists
     *
     * Checks if an actual method exists
     *
     * @param string $name method name
     *
     * @return bool
     */
    protected function _methodExists($name)
    {
        return method_exists($this, $name);
    }

    /**
     * Add event listener
     *
     * Registers an event listener for the event, identified by <var>$name</var>,
     * using <var>$listener</var> as the listener callback:
     * <code> $myObj->addEvent('foo', 'onFoo');</code>
     *
     * @throws AeObjectException #400 on invalid name
     *
     * @param string                    $name     event name, case insensitive
     * @param AeEvent_Listener|callback $listener event handling function
     *
     * @return AeEvent_Listener
     */
    public function addEvent($name, $listener)
    {
        $type = AeType::of($name);

        if ($type != 'string') {
            throw new AeObjectException('Invalid name type: expecting string, ' . $type . ' given', 400);
        }

        $name     = strtolower((string) $name);
        $listener = AeEvent::listener($listener);

        if (!is_array($this->___events)) {
            $this->___events = array();
        }

        if (!isset($this->___events[$name]) || !is_array($this->___events[$name])) {
            $this->___events[$name] = array();
        }

        $this->___events[$name][] = $listener;

        return $listener;
    }

    /**
     * Remove event listener
     *
     * Unregisteres an event listener for the event, identified by <var>$name</var>,
     * using <var>$listener</var> to identify the actual function to remove.
     *
     * @throws AeObjectException #400 on invalid name
     *
     * @param string           $name
     * @param AeEvent_Listener $listener
     *
     * @return AeObject self
     */
    public function removeEvent($name, AeEvent_Listener $listener)
    {
        $type = AeType::of($name);

        if ($type != 'string') {
            throw new AeObjectException('Invalid name type: expecting string, ' . $type . ' given', 400);
        }

        if (!is_array($this->___events)) {
            return $this;
        }

        $name = strtolower((string) $name);

        if (!isset($this->___events[$name]) || !is_array($this->___events[$name])) {
            return $this;
        }

        $events = $this->___events[$name];

        foreach ($events as $i => $l)
        {
            if ($listener === $l) {
                unset($events[$i]);
                $this->___events[$name] = array_values($events);
                break;
            }
        }

        return $this;
    }

    /**
     * Add multiple event listeners
     *
     * Allows to add several event listeners to different events:
     * <code> $listeners = $myObj->addEvents(array(
     *     'foo' => new AeCallback('Handler', 'onFoo'),
     *     'bar' => new AeCallback('Handler', 'onBar')
     * ));</code>
     *
     * @throws AeObjectException #400 on invalid events type
     *
     * @param array $events
     *
     * @return array an array of event AeEvent_Listener objects
     */
    public function addEvents($events)
    {
        $type = AeType::of($name);

        if ($type != 'array') {
            throw new AeObjectException('Invalid events type: expecting array, ' . $type . ' given', 400);
        }

        $return = array();

        foreach ($events as $name => $listener) {
            $return[$name] = $this->addEvent($name, $listener);
        }

        return $return;
    }

    /**
     * Remove several event listeners
     *
     * Allows to remove several event listeners from different events:
     * <code> $myObj->removeEvents(array(
     *     'foo' => $listeners['foo'],
     *     'bar' => $listeners['bar']
     * ));</code>
     *
     * You can also remove all event listeners for a certain event:
     * <code> $myObj->removeEvents('foo');</code>
     *
     * @throws AeObjectException #400 on invalid events type
     *
     * @param array|string $events
     *
     * @return array an array of removed AeEvent_Listener objects
     */
    public function removeEvents($events)
    {
        $type = AeType::of($name);

        if ($type != 'array' && $type != 'string') {
            throw new AeObjectException('Invalid events type: expecting array or string, ' . $type . ' given', 400);
        }

        if ($type == 'string')
        {
            $name = strtolower((string) $events);

            if (!is_array($this->___events)) {
                return $this;
            }

            if (isset($this->___events[$name]) && is_array($this->___events[$name])) {
                $this->___events[$name] = array();
            }

            return $this;
        }

        foreach ($events as $name => $listener) {
            $this->removeEvent($name, $listener);
        }

        return $this;
    }

    /**
     * Fire event
     *
     * Triggers an event, identified by <var>$name</var>, using <var>$args</var>
     * as additional event parameters:
     * <code> $myObj->fireEvent('foo', array('foo', 'bar', 'baz'));</code>
     *
     * An event handler for such fireEvent call might look something like this:
     * <code> function onFoo($event, $foo, $bar, $baz)
     * {
     *     var_dump($foo, $bar, $baz);
     * }</code>
     *
     * The second parameter may also be a single variable instead of an array,
     * if you only want to pass one parameter to the event handler:
     * <code> $myObj->fireEvent('foo', 'bar');</code>
     *
     * This method returns false, if an event listener requested to stop the
     * default event action (see {@link AeEvent::preventDefault()}), true
     * otherwise. Note, that not all classes support the preventDefault action.
     *
     * @throws AeObjectException #400 on invalid name
     *
     * @param string       $name event name
     * @param mixed|array  $args event parameters
     *
     * @return bool
     */
    public function fireEvent($name, $args = array())
    {
        $type = AeType::of($name);

        if ($type != 'string') {
            throw new AeObjectException('Invalid name type: expecting string, ' . $type . ' given', 400);
        }

        if (!is_array($this->___events)) {
            return true;
        }

        $name = strtolower((string) $name);

        if (!isset($this->___events[$name]) || !is_array($this->___events[$name])) {
            return true;
        }

        $events = $this->___events[$name];

        if (count($events) > 0)
        {
            $event = new AeEvent($name, $this);

            if (!is_array($args)) {
                $args = array($event, $args);
            } else {
                array_unshift($args, $event);
            }

            foreach ($events as $listener)
            {
                $listener->call($args);

                if ($event->getStopPropagation()) {
                    break;
                }
            }

            if ($event->getPreventDefault()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clone events
     *
     * Copies all events from <var>$from</var> object to current object. If an
     * optional <var>$name</var> parameter is specified and is a string, only
     * event listeners for that event are copied.
     *
     * Note that AeEvent_Listener objects are not actually cloned, but are set
     * directly to several objects, so modifying a listener will affect all
     * objects, to which it was assigned.
     *
     * This method returns different array structures depending on the value of
     * <var>$name</var> parameter:
     * <code> print_r($myObj->cloneEvents($myOtherObj));
     * print_r($myObj->cloneEvents($myOtherObj, 'foo'));</code>
     *
     * The code above will result in something like this:
     * <pre> Array(
     *     'foo' => Array(
     *         0 => AeEvent_Listener,
     *         1 => AeEvent_Listener
     *     ),
     *     'bar' => Array(
     *         0 => AeEvent_Listener
     *     )
     * )
     *
     * Array(
     *     0 => AeEvent_Listener,
     *     1 => AeEvent_Listener
     * )</pre>
     *
     * @param AeObject $from
     * @param string   $name
     *
     * @return AeObject self
     */
    public function cloneEvents(AeObject $from, $name = null)
    {
        return AeEvent::copy($from, $this, $name);
    }
}

/**
 * Object exception class
 *
 * Object-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeObjectException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Object');
        parent::__construct($message, $code);
    }
}

?>