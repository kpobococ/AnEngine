<?php
/**
 * Node class file
 *
 * See {@link AeNode} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Node class
 *
 * This is a basic node class. It allows to get or set any parameter as if they
 * were defined inside the class. It also has the __set_state method
 * implemented, making it useable via {@link var_export()}. This is used in the
 * {@link AeSettings} library's {@link AeSettings_Php PHP} driver.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeNode extends AeObject
{
    /**
     * An array of custom node properties
     * @var array
     */
    protected $_properties = array();

    /**
     * Constructor
     *
     * @param array $properties an associative array of properties to bind
     */
    public function __construct(array $properties = array())
    {
        if (!empty($properties)) {
            $this->bind($properties);
        }
    }

    /**
     * Node property getter
     *
     * @param string $name    name of the property (public or protected)
     * @param mixed  $default value to return if requested property not set
     *                        or not found
     * 
     * @return mixed property value, default value if property not set
     */
    public function get($name, $default = null)
    {
        $name = (string) $name;

        if ($this->propertyExists($name)) {
            return parent::get($name, $default);
        }

        return isset($this->_properties[$name]) ? $this->_properties[$name] : $default;
    }

    /**
     * Node property setter
     *
     * @param string $name  name of the property (public or protected)
     * @param mixed  $value new value of the property
     *
     * @return AeNode self
     */
    public function set($name, $value)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::set($name, $value);
        }

        $this->_properties[$name] = $value;

        return $this;
    }

    /**
     * Node property unsetter
     *
     * @param string $name property name
     *
     * @return AeNode self
     */
    public function clear($name)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::clear($name);
        }

        unset($this->_properties[$name]);

        return $this;
    }

    /**
     * Virtual property getter support method
     *
     * This method is called every time an {@link isset()} operation is
     * performed on an undefined or protected property.
     *
     * @param string $name property name
     *
     * @return bool true if property is not null, false otherwise
     */
    public function __isset($name)
    {
        if ($this->propertyExists($name)) {
            return parent::__isset($name);
        }

        return isset($this->_properties[$name]);
    }

    /**
     * Bind array to node
     *
     * Bind an associative array of properties to the node. Non-string or
     * numeric string properties will be skipped.
     *
     * @param array $properties an associative array of properties
     *
     * @return AeNode self
     */
    public function bind($properties)
    {
        if (count($properties) > 0)
        {
            foreach ($properties as $property => $value)
            {
                if (!is_string($property) || is_numeric($property)) {
                    continue;
                }

                $this->set($property, $value);
            }
        }

        return $this;
    }

    /**
     * Get node with bound state
     *
     * Return an instance of a node with all the properties passed in
     * <var>$array</var> associative array assigned to it:
     *
     * <code> $node = AeNode::__set_state(array(
     *     'foo' => 'hello',
     *     'bar' => 'world'
     * ));
     *
     * echo $node->get('foo') . ' ' . $node->get('bar'); // prints "hello world"</code>
     *
     * @param array $array an associative array of properties
     *
     * @return object|AeNode a resulting node or custom object
     */
    public static function __set_state($array)
    {
        if (count($array) > 0) {
            $node = new AeNode;
            $node->bind($array);
        }

        return $node;
    }
}

/**
 * Node exception class
 *
 * Node-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeNodeException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Node');
        parent::__construct($message, $code);
    }
}
?>