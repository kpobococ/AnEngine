<?php
/**
 * XML node class file
 *
 * See {@link AeXml_Node} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * XML node class
 *
 * This is an xml node class. It represents a single XML element, along with all
 * of it's properties, child nodes or data.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeXml_Node extends AeNode
{
    /**
     * Element tag name
     * @var string
     */
    protected $_name;

    /**
     * Element text data
     * @var string
     */
    protected $_data = null;

    /**
     * Element children
     *
     * This is null for empty elements
     *
     * @var array
     */
    protected $_children = array();

    /**
     * Element parent
     *
     * This is null for root element
     *
     * @var AeXml_Node
     */
    protected $_parent = null;

    /**
     * Element offset
     *
     * This indicates the element's offset position in an array of it's parent's
     * child elements.
     *
     * This is null for root element
     *
     * @var int
     */
    protected $_position = null;

    /**
     * Constructor
     *
     * You can create a text node by passing '@text' as element's name
     *
     * @param string $name element tag name
     */
    public function __construct($name)
    {
        $this->name = preg_replace('#\s+#', '', (string) $name);
    }

    /**
     * Get data status
     * 
     * @return bool
     */
    public function hasData()
    {
        return !is_null($this->_data);
    }

    /**
     * Get properties status
     *
     * @return bool
     */
    public function hasProperties()
    {
        return !empty($this->_properties);
    }

    /**
     * Get children status
     *
     * @param AeString|string $filter if used, detects if element has children
     *                                with that name
     *
     * @return bool true if children found, false otherwise
     */
    public function hasChildren($filter = null)
    {
        if (is_null($filter)) {
            return !empty($this->_children);
        }

        if (!$this->hasChildren()) {
            return false;
        }

        foreach ($this->_children as $child)
        {
            if ($child->name == (string) $filter) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get parent status
     *
     * False means this element is a root element
     *
     * @return bool
     */
    public function hasParent()
    {
        return !is_null($this->_parent);
    }

    /**
     * Get child status
     *
     * @param AeXml_Node|int $child either an AeXml_Node instance or an offset
     *                              value to check
     *
     * @return bool
     */
    public function hasChild($child)
    {
        if (!$this->hasChildren()) {
            return false;
        }

        if ($child instanceof AeXml_Node) {
            // *** Find the node
            return ($child->parent === $this);
        }

        // *** Find the offset
        return (count($this->children) >= $child);
    }

    /**
     * Get property status
     *
     * @param string $name property name
     *
     * @return bool
     */
    public function has($name)
    {
        if (!$this->hasProperties()) {
            return false;
        }

        if (isset($this->_properties[(string) $name])) {
            return true;
        }

        return false;
    }

    /**
     * Get element children
     *
     * If <var>$filter</var> parameter is set, it will be used as an element tag
     * name filter for a resulting array
     *
     * @param AeString|string $filter
     *
     * @return AeArray
     */
    public function getChildren($filter = null)
    {
        if (!$this->hasChildren()) {
            return AeType::wrapReturn(array());
        }

        if (is_null($filter)) {
            return AeType::wrapReturn($this->_children);
        }

        $return = array();

        foreach ($this->children as $node)
        {
            if ($node->getName() == (string) $filter) {
                array_push($return, $node);
            }
        }

        return AeType::wrapReturn($return);
    }

    /**
     * Get element properties
     *
     * @return AeArray
     */
    public function getProperties()
    {
        return AeType::wrapReturn($this->_properties);
    }

    /**
     * Get first child
     *
     * Returns null if there are no child elements
     *
     * If <var>$filter</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element
     *
     * @param AeString|string $filter
     *
     * @return AeXml_Node
     */
    public function getFirst($filter = null)
    {
        if (!$this->hasChildren()) {
            return null;
        }

        $children = $this->getChildren($filter);

        if (count($children) == 0) {
            return null;
        }

        return $children[0];
    }

    /**
     * Get last child
     *
     * Returns null if there are no child elements
     *
     * If <var>$filter</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element
     *
     * @param AeString|string $filter
     *
     * @return AeXml_Node
     */
    public function getLast($filter = null)
    {
        if (!$this->hasChildren()) {
            return null;
        }

        $children = $this->getChildren($filter);
        $length   = count($children);

        if ($length == 0) {
            return null;
        }

        return $children[$length - 1];
    }

    /**
     * Get next sibling
     *
     * Returns null if there is no next sibling
     *
     * If <var>$filter</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element
     *
     * @param AeString|string $filter
     *
     * @return AeXml_Node
     */
    public function getNext($filter = null)
    {
        if (is_null($filter)) {
            return $this->parent->getChild($this->position + 1);
        }

        $children = $this->parent->children;
        $length   = count($children);
        $filter   = (string) $filter;

        for ($i = $this->position + 1; $i < $length; $i++)
        {
            if ($children[$i]->name == $filter) {
                return $children[$i];
            }
        }

        return null;
    }

    /**
     * Get previous sibling
     *
     * Returns null if there is no previous sibling
     *
     * If <var>$filter</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element
     *
     * @param AeString|string $filter
     *
     * @return AeXml_Node
     */
    public function getPrevious($filter = null)
    {
        if ($this->position == 0) {
            return null;
        }

        if (is_null($filter)) {
            return $this->parent->getChild($this->position - 1);
        }

        $children = $this->parent->children;
        $filter   = (string) $filter;

        for ($i = 0; $i < $this->position; $i ++)
        {
            if ($children[$i]->name == $filter) {
                return $children[$i];
            }
        }

        return null;
    }

    /**
     * Get property value
     *
     * @param string $name    property name
     * @param string $default property default value
     *
     * @return AeString
     */
    public function get($name, $default = null)
    {
        return AeType::wrapReturn(parent::get($name, $default));
    }

    /**
     * Get child element
     *
     * Returns null if there is no child with an <var>$offset</var> position
     *
     * @param int $offset
     *
     * @return AeXml_Node
     */
    public function getChild($offset)
    {
        return $this->hasChild($offset) ? $this->children[$offset] : null;
    }

    /**
     * Get element data
     *
     * @param mixed $default default data to return
     *
     * @return AeString
     */
    public function getData($default = null)
    {
        if (!isset($this->_data)) {
            return AeType::wrapReturn($default);
        }

        return AeType::wrapReturn($this->_data);
    }

    /**
     * Get element position
     *
     * Checks current assigned position for validity and returns it. If current
     * element is not found within the children of assigned parent, tries to
     * find the correct position manually. If position not found, parent is
     * unassigned.
     *
     * This method can essentially be used as a validator for child-parent
     * relations between nodes.
     *
     * @return int or false, if position is incorrect
     */
    public function getPosition()
    {
        // *** No parent at all
        if (!$this->hasParent()) {
            $this->_position = null;

            return false;
        }

        if (!is_null($this->_position) && $this->_parent->children[$this->_position] === $this) {
            // *** Position set and is correct
            return $this->_position;
        }

        // *** Position incorrect, let's find it manually
        foreach ($this->_parent->children as $offset => $child)
        {
            if ($child === $this) {
                $this->_position = $offset;
                return $offset;
            }
        }

        // *** Wrong parent set
        $this->_parent   = null;
        $this->_position = null;

        return false;
    }

    /**
     * Add child element
     *
     * @param AeXml_Node|string $child element to add or a tag name to create
     *
     * @return AeXml_Node added child
     */
    public function addChild($child)
    {
        // *** This node cannot have child nodes
        if ($this->name == '@text') {
            throw new AeXmlNodeException('Cannot add children to text nodes', 406);
        }

        // *** Pre-created node validations
        if ($child instanceof AeXml_Node && is_int($child->getPosition()))
        {
            if ($child->parent === $this) {
                // *** Already a child
                return $child;
            }

            // *** Remove current connections
            $child->parent->removeChild($child);
        } else {
            $child = new AeXml_Node($child);
        }

        // *** Convert existing character data to text nodes
        if ($this->hasData()) {
            $data        = $this->data;
            $this->_data = null;

            $this->addChild('@text')->setData($data);
        }

        // *** Finally, assign proper values to child properties
        $child->_position  = count($this->children);
        $child->_parent    = $this;
        $this->_children[] = $child;

        return $child;
    }

    /**
     * Remove child element
     *
     * @param AeXml_Node|int $child element to remove or it's offset value
     *
     * @return AeXml_Node removed element
     */
    public function removeChild($child)
    {
        // *** Pre-created node validations
        if ($child instanceof AeXml_Node)
        {
            if (!is_int($child->getPosition()) || $child->parent !== $this) {
                throw new AeXmlNodeException('Passed element is not a direct child of current element', 409);
            }
        } else {
            $child = $this->getChild($child);

            if (is_null($child)) {
                throw new AeXmlNodeException('Offset not found in an element', 404);
            }
        }

        // *** Remove child from array and reset child's properties
        unset($this->_children[$child->_position]);

        $this->_children  = array_values($this->_children);
        $child->_position = null;
        $child->_parent   = null;

        return $child;
    }

    /**
     * Set element parent
     *
     * Sets the element's parent element to the element, passed in the
     * <var>$parent</var> parameter
     *
     * @param AeXml_Node $parent
     *
     * @return AeXml_Node current node
     */
    public function setParent(AeXml_Node $parent)
    {
        // *** Remove current parent first
        if ($this->hasParent()) {
            $this->parent->removeChild($this);
        }

        // *** Add element as a parent's child instead
        if (!$parent->addChild($this)) {
            throw new AeXmlNodeException('Cannot set node parent', 406);
        }

        return $this;
    }

    /**
     * Set element property
     *
     * @param string $name  property name
     * @param string $value property value
     *
     * @return AeXml_Node current node
     */
    public function set($name, $value)
    {
        $name = preg_replace('#\s+#', '', (string) $name);

        parent::set($name, $value);

        return $this;
    }

    public function setProperties($properties)
    {
        $type = AeType::of($properties);

        if ($type != 'array') {
            throw new AeXmlNodeException('Invalid properties type: expecting array, ' . $type . ' given', 400);
        }

        if ($properties instanceof AeType) {
            $properties = $properties->getValue();
        }

        $this->_properties = $properties;
    }

    /**
     * Set element data
     *
     * @param string $value
     *
     * @return AeXml_Node current node
     */
    public function setData($value)
    {
        if ($this->hasChildren() && $this->children->length() > $this->getChildren('@text')->length()) {
            throw new AeXmlNodeException('Cannot set data of nodes with child elements', 406);
        }

        $this->_data = (string) $value;

        return $this;
    }

    /**
     * Get formed XML
     *
     * Returns a well formed XML, treating current node as a root element. If
     * <var>level</var> parameter is greater than 0, then there will be no XML
     * declaration and there will be 4 spaces for each level. This allows you
     * to generate XML parts as you want them
     *
     * @param int $level current element level
     *
     * @return string
     */
    public function toString($level = 0)
    {
        $return = '';

        if ($level == 0) {
            $return .= '<' . '?xml version="1.0" encoding="UTF-8" standalone="yes"?' . '>' . "\n";
            $pre     = '';
        } else {
            $pre = str_repeat(' ', $level * 4);
        }

        if ($this->getName() == '@text') {
            return $pre . $this->_getCleanData();
        }

        $return .= $pre . '<' . $this->getName();

        if ($this->hasProperties())
        {
            foreach ($this->getProperties() as $property => $value) {
                $return .= ' ' . $property . '="' . str_replace('"', '&quot;', $value) . '"';
            }
        }

        if ($this->hasChildren() || $this->hasData())
        {
            $return .= '>';

            if ($this->hasChildren())
            {
                $return .= "\n";

                foreach ($this->getChildren() as $child) {
                    $return .= $child->toString($level + 1) . "\n";
                }

                $return .= $pre . '</' . $this->getName() . '>';
            } else {
                $return .= $this->_getCleanData();
                $return .= '</' . $this->getName() . '>';
            }
        } else {
            $return .= ' />';
        }

        return $return;
    }

    /**
     * Get safe data
     *
     * Returns element data, escaped for safe writing or outputting.
     *
     * @return string
     */
    private function _getCleanData()
    {
        $data = $this->getData();
        $data = str_replace('&', '&amp;', $data);
        $data = str_replace('<', '&lt;' , $data);
        $data = str_replace('>', '&gt;' , $data);

        return $data;
    }

    /**
     * Write XML to file
     *
     * Writes XML to the specified file, using current element as a root
     * element. If you do not specify any file extension, xml is assumed
     *
     * @param string $file
     *
     * @return bool true on success, false otherwise
     */
    public function save($file)
    {
        $file = (string) $file;

        if (!strpos($file, '.')) {
            $file .= '.xml';
        }

        $file = AeFile::getInstance($file);

        if (!$file->exists()) {
            $file->create();
        }

        if (!$file->write((string) $this)) {
            return false;
        }

        return true;
    }
}

/**
 * XML node exception class
 *
 * XML node-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeXmlNodeException extends AeXmlException
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