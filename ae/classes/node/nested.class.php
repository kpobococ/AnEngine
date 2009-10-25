<?php
/**
 * Nested node class file
 *
 * See {@link AeNode_Nested}
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Nested node class
 *
 * This is a nested node class. It allows to get or set any parameter, like the
 * regular node, but also allows for nested property get and set. A nested
 * property is essentially a key of an array property:
 * <code> $node = new AeNode_Nested(array('foo' => array('bar' => 'baz')));
 *
 * print_r($node->get('foo')); // prints array('bar' => 'baz')
 * echo $node->get('foo.bar'); // prints 'baz'
 *
 * $node->set('bar', 'baz');
 * $node->set('foo.baz', 'baz');
 *
 * print_r($node->get('foo')); // prints array('bar' => 'baz', 'baz' => 'baz')</code>
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeNode_Nested extends AeNode
{
    /**
     * Get property
     *
     * @param string $name    property name
     * @param mixed  $default property default value
     *
     * @return mixed property value, default value if property not set
     */
    public function get($name, $default = null)
    {
        $name = (string) $name;

        if ($this->propertyExists($name)) {
            return parent::get($name, $default);
        }

        $return = $this->_getByKey($name, $this->_properties);

        return is_null($return) ? $default : $return;
    }

    /**
     * Set property
     *
     * @param string $name  property name
     * @param mixed  $value property value
     *
     * @return bool true on success, false otherwise
     */
    public function set($name, $value)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::set($name, $value);
        }

        return $this->_setByKey($name, $value, $this->_properties);
    }

    /**
     * Clear property
     *
     * @param string $name property name
     *
     * @return mixed former property value
     */
    public function clear($name)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::clear($name);
        }

        return $this->_clearByKey($name, $this->_properties);
    }

    /**
     * Get nested property
     *
     * @param string $key
     * @param array  $source
     *
     * @return mixed
     */
    protected function _getByKey($key, &$source)
    {
        if (strpos($key, '.'))
        {
            list ($section, $key) = explode('.', $key, 2);

            if (!isset($source[$section])) {
                return null;
            }

            return $this->_getByKey($key, $source[$section]);
        }

        if (!isset($source[$key])) {
            return null;
        }

        return $source[$key];
    }

    /**
     * Set nested property
     *
     * @param string $key
     * @param mixed  $value
     * @param array  $source
     *
     * @return bool
     */
    protected function _setByKey($key, $value, &$source)
    {
        if (strpos($key, '.'))
        {
            list ($section, $key) = explode('.', $key, 2);

            if (!isset($source[$section])) {
                $source[$section] = array();
            }

            return $this->_setByKey($key, $value, $source[$section]);
        }

        $source[$key] = $value;

        return true;
    }

    /**
     * Clear nested property
     *
     * @param string $key
     * @param array  $source
     *
     * @return mixed
     */
    protected function _clearByKey($key, &$source)
    {
        if (strpos($key, '.'))
        {
            list ($section, $key) = explode('.', $key, 2);

            if (isset($source[$section])) {
                return $this->_clearByKey($key, $source[$section]);
            }

            return null;
        }

        if (isset($source[$key])) {
            $value = $source[$key];

            unset($source[$key]);

            return $value;
        }

        return null;
    }
}

/**
 * Nested node exception class
 *
 * Nested node-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeNestedNodeException extends AeNodeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Nested');
        parent::__construct($message, $code);
    }
}
?>