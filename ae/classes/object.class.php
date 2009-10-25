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
class AeObject
{
    protected $___events;

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
     * @param string $name  name of the property (public or protected)
     * @param mixed  $value new value of the property
     *
     * @return bool true if property was found and set, false otherwise
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
            return true;
        }

        // *** Check if _property exists
        if ($this->_propertyExists('_' . $name)) {
            $_name        = '_' . $name;
            $this->$_name = $value;
            return true;
        }

        return false;
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
     * @return bool true if property was found and unset, false otherwise
     */
    public function clear($name)
    {
        $value = null;

        if ($this->propertyExists($name, 'set')) {
            $value = $this->get($name);
            $this->set($name, null);
        }

        return $value;
    }

    /**
     * Check if property exists
     *
     * Returns true, if property exists and can be used with getters and setters,
     * false otherwise
     *
     * @param string $name property name
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
     * Returns true, if method exists and can be called. This method also
     * returns true for all virtual getters and setters
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

    protected function _propertyExists($name)
    {
        return property_exists($this, $name);
    }

    protected function _methodExists($name)
    {
        return method_exists($this, $name);
    }

    public function addEvent($name, $listener)
    {
        $type = AeType::typeOf($name);

        if ($type != 'string') {
            throw new AeObjectException('Invalid name type: expecting string, ' . $type . ' given', 400);
        }

        return AeEvent::add($name, $listener, $this);
    }

    public function removeEvent($name, AeEvent_Listener $listener)
    {
        $type = AeType::typeOf($name);

        if ($type != 'string') {
            throw new AeObjectException('Invalid name type: expecting string, ' . $type . ' given', 400);
        }

        return AeEvent::remove($name, $listener, $this);
    }

    public function addEvents($events)
    {
        $type = AeType::typeOf($name);

        if ($type != 'array') {
            throw new AeObjectException('Invalid events type: expecting array, ' . $type . ' given', 400);
        }

        return AeEvent::add($events, null, $this);
    }

    public function removeEvents($events)
    {
        $type = AeType::typeOf($name);

        if ($type == 'string')
        {
            if (!is_array($this->___events)) {
                return array();
            }

            $return = array();

            foreach ($this->___events as $name) {
                $return[$name] = AeEvent::remove($name, null, $this);
            }

            return $return;
        } else if ($type != 'array') {
            throw new AeObjectException('Invalid events type: expecting array or string, ' . $type . ' given', 400);
        }

        return AeEvent::remove($events, null, $this);
    }

    public function fireEvent($name, $args = null)
    {
        return AeEvent::fire($name, $args, $this);
    }

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