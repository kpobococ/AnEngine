<?php
/**
 * XML element class file
 *
 * See {@link AeXml_Entity_Element} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * XML element class
 *
 * This is an xml element class. It represents a single XML element, along with
 * all of it's attributes, child elements and/or data.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeXml_Entity_Element extends AeXml_Entity implements AeInterface_Xml_Element
{
    /**
     * Element tag name
     * @var string
     */
    protected $_name;

    /**
     * Element children
     * @var array
     */
    protected $_children = array();

    /**
     * Element attributes
     * @var array
     */
    protected $_attributes = array();

    /**
     * Constructor
     *
     * @param string $name element tag name
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    public function getName()
    {
        return $this->_name;
    }

    /**
     * Set element name
     *
     * @throws AeXmlEntityElementException #400 on invalid name
     *
     * @param string $name element name
     *
     * @return AeXml_Entity_Element self
     */
    public function setName($name)
    {
        $name = $this->_getCleanName($name);

        if (empty($name)) {
            throw new AeXmlEntityElementException('Invalid name value: expecting XML Name', 400);
        }

        $this->_name = $name;

        return $this;
    }

    public function hasAttributes()
    {
        return !empty($this->_attributes);
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Set element attributes
     *
     * @throws AeXmlEntityElementException #400 on invalid value passed
     *
     * @param array $attributes an associative array of attributes
     *
     * @return AeXml_Entity_Element self
     */
    public function setAttributes($attributes)
    {
        $type = AeType::of($attributes);

        if ($type != 'array') {
            throw new AeXmlEntityElementException('Invalud value passed: expecting array, ' . $type . ' given', 400);
        }

        if ($attributes instanceof AeArray) {
            $attributes = $attributes->getValue();
        }

        $this->_attributes = array();

        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Get attribute status
     *
     * @throws AeXmlEntityElementException #400 on invalid name
     *
     * @param string $name attribute name
     *
     * @return bool true if attribute exists, false otherwise
     */
    public function hasAttribute($name)
    {
        if (!$this->hasAttributes()) {
            return false;
        }

        $name = $this->_getCleanName($name);

        if (empty($name)) {
            throw new AeXmlEntityElementException('Invalid name value: expecting XML Name', 400);
        }

        if (isset($this->_attributes[$name])) {
            return true;
        }

        return false;
    }

    /**
     * Get attribute value
     *
     * @throws AeXmlEntityElementException #400 on invalid name
     *
     * @param string $name    attribute name
     * @param string $default attribute default value
     *
     * @return mixed attribute value
     */
    public function getAttribute($name, $default = null)
    {
        $name = $this->_getCleanName($name);

        if (empty($name)) {
            throw new AeXmlEntityElementException('Invalid name value: expecting XML Name', 400);
        }

        return $this->hasAttribute($name) ? $this->_attributes[$name] : $default;
    }

    /**
     * Set attribute value
     *
     * @throws AeXmlEntityElementException #400 on invalid name
     *
     * @param string $name  attribute name
     * @param string $value attribute value
     *
     * @return AeXml_Entity_Element self
     */
    public function setAttribute($name, $value)
    {
        $name = $this->_getCleanName($name);

        if (empty($name)) {
            throw new AeXmlEntityElementException('Invalid name value: expecting XML Name', 400);
        }

        $this->_attributes[$name] = $value;

        return $this;
    }

    /**
     * Get children status
     *
     * An optional <var>$name</var> parameter can be used to tell if an element
     * has child elements with <var>$name</var> tag name.
     *
     * @throws AeXmlEntityElementException #400 on invalid name
     *
     * @param string $name element name
     *
     * @return bool true if children found, false otherwise
     */
    public function hasChildren($name = null)
    {
        $empty = empty($this->_children);

        if (is_null($name)) {
            return !$empty;
        }

        if ($empty) {
            return false;
        }

        $name = $this->_getCleanName($name);

        if (empty($name)) {
            throw new AeXmlEntityElementException('Invalid name value: expecting XML Name', 400);
        }

        foreach ($this->_children as $child)
        {
            if ($child instanceof AeInterface_Xml_Element && $child->getName() == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get element children
     *
     * An optional <var>$name</var> parameter can be used to get child elements
     * with <var>$name</var> tag name.
     *
     * @throws AeXmlEntityElementException #400 on invalid name
     *
     * @param string $name element name
     *
     * @return array an array of {@link AeInterface_Xml_Entity}
     */
    public function getChildren($name = null)
    {
        if (!$this->hasChildren()) {
            return array();
        }

        if (is_null($name)) {
            return $this->_children;
        }

        $return = array();
        $name   = $this->_getCleanName($name);

        if (empty($name)) {
            throw new AeXmlEntityElementException('Invalid name value: expecting XML Name', 400);
        }

        foreach ($this->_children as $child)
        {
            if ($child instanceof AeInterface_Xml_Element && $child->getName() == $name) {
                array_push($return, $child);
            }
        }

        return $return;
    }

    public function setChildren($children)
    {
        foreach ($this->_children as $child) {
            $this->removeChild($child);
        }

        foreach ($children as $child) {
            $this->addChild($child);
        }

        return $this;
    }

    /**
     * Get child status
     *
     * @throws AeXmlEntityElementException #400 on invalid position value
     *
     * @param AeInterface_Xml_Entity|int $position either an integer position or
     *                                             AeInterface_Xml_Entity instance
     *
     * @return bool true if child is present, false otherwise
     */
    public function hasChild($position)
    {
        if (!$this->hasChildren()) {
            return false;
        }

        if ($position instanceof AeInterface_Xml_Entity) {
            // *** Find the entity
            return ($position->getParent() === $this);
        }

        $type = AeType::of($position);

        if ($type != 'integer') {
            throw new AeXmlEntityElementException('Invalid value passed: expecting integer, ' . $type . ' given', 400);
        }

        if ($position instanceof AeInteger) {
            $position = $position->getValue();
        }

        if ($position < 0) {
            throw new AeXmlEntityElementException('Invalid value passed: position cannot be less than zero', 400);
        }

        // *** Find the offset
        return (count($this->_children) >= $position);
    }

    /**
     * Get child entity
     *
     * @throws AeXmlEntityElementException #404 if position not found
     *
     * @param int $position an integer position
     *
     * @return AeInterface_Xml_Entity child entity
     */
    public function getChild($position)
    {
        if (!$this->hasChild($position)) {
            throw new AeXmlEntityElementException('Cannot get child: position not found', 404);
        }

        if ($position instanceof AeInteger) {
            $position = $position->getValue();
        }

        return $this->_children[$position];
    }

    /**
     * Set child entity
     *
     * Adds the <var>$child</var> entity as a child element at
     * <var>$position</var>. Does not replace any entities. Instead, an entity
     * at <var>$position</var> and all following entities are moved down.
     *
     * @throws AeXmlEntityElementException #400 on invalid position value
     *
     * @param int                    $position an integer position
     * @param AeInterface_Xml_Entity $child    child entity
     *
     * @return AeXml_Entity_Element self
     */
    public function setChild($position, AeInterface_Xml_Entity $child)
    {
        $type = AeType::of($position);

        if ($type != 'integer') {
            throw new AeXmlEntityElementException('Invalid value passed: expecting integer, ' . $type . ' given', 400);
        }

        if ($position instanceof AeInteger) {
            $position = $position->getValue();
        }

        if ($position < 0) {
            throw new AeXmlEntityElementException('Invalid value passed: position cannot be less than zero', 400);
        }

        if ($child->hasParent()) {
            $child->getParent()->removeChild($child);
        }

        $length = count($this->_children);

        if ($position > $length) {
            throw new AeXmlEntityElementException('Invalid value passed: position cannot be greater than child count', 400);
        }

        $entities = array();

        if ($this->hasChild($position))
        {

            for ($i = $position; $i < $length; $i++) {
                $entities[] = $this->_children[$i];
                $this->removeChild($i);
            }
        }

        $this->addChild($child);

        if (!empty($entities))
        {
            foreach ($entities as $entity) {
                $this->addChild($entity);
            }
        }

        return $this;
    }

    public function getFirst($name = null)
    {
        if (!$this->hasChildren()) {
            return null;
        }

        $children = $this->getChildren($name);

        if (count($children) == 0) {
            return null;
        }

        return $children[0];
    }

    public function getLast($name = null)
    {
        if (!$this->hasChildren()) {
            return null;
        }

        $children = $this->getChildren($name);
        $length   = count($children);

        if ($length == 0) {
            return null;
        }

        return $children[$length - 1];
    }

    /**
     * Add child entity
     *
     * @throws AeXmlEntityElementException #400 on invalid value passed
     *
     * @param AeInterface_Xml_Entity|string $child entity or tag name to create
     *
     * @return AeInterface_Xml_Element added child
     */
    public function addChild($child)
    {
        if (!($child instanceof AeInterface_Xml_Entity))
        {
            $type = AeType::of($child);

            if ($type != 'string') {
                throw new AeXmlEntityElementException('Invalid value passed: expecting XML entity or string, ' . $type . ' given', 400);
            }

            $child = new AeXml_Entity_Element($child);
        }

        if ($child->hasParent()) {
            $child->getParent()->removeChild($child);
        }

        if ($this->hasData()) {
            $this->_children[] = new AeXml_Entity_Data($this->getData());
            $this->_data       = null;
        }

        $child->setParent($this);
        $child->setPosition(count($this->_children));

        $this->_children[] = $child;

        return $child;
    }

    /**
     * Remove child entity
     *
     * @throws AeXmlEntityElementException #400 on invalid value passed
     * @throws AeXmlEntityElementException #412 if entity is not an element child
     *
     * @param AeInterface_Xml_Entity|int $child entity or position to remove
     *
     * @return AeInterface_Xml_Entity removed entity
     */
    public function removeChild($child)
    {
        if (!($child instanceof AeInterface_Xml_Entity))
        {
            $type = AeType::of($child);

            if ($type != 'integer') {
                throw new AeXmlEntityElementException('Invalid value passed: expecting XML entity or integer, ' . $type . ' given', 400);
            }

            $child = $this->getChild($child);
        } else if ($child->getParent() !== $this) {
            throw new AeXmlEntityElementException('Invalid value passed: entity is not an element child', 412);
        }

        $index = $child->getPosition();
        $child->setPosition(null);

        // *** Remove child from array and reset child's properties
        unset($this->_children[$index]);

        $this->_children = array_values($this->_children);
        $length          = count($this->_children);

        for ($i = $index; $i < $length; $i++) {
            $this->_children[$i]->setPosition($i);
        }

        return $child;
    }

    /**
     * Add child entity
     *
     * @throws AeXmlEntityElementException #400 on invalid value passed
     *
     * @param AeInterface_Xml_Entity|string $data entity or data to create
     *
     * @return AeInterface_Xml_Entity added child
     */
    public function addData($data)
    {
        if (!($data instanceof AeInterface_Xml_Entity))
        {
            $type = AeType::of($data);

            if ($type != 'string') {
                throw new AeXmlEntityElementException('Invalid value passed: expecting XML entity or string, ' . $type . ' given', 400);
            }

            $data = new AeXml_Entity_Data($data);
        }

        return $this->addChild($data);
    }

    public function toString($level = 0)
    {
        if ($level instanceof AeScalar) {
            $level = $level->toInteger()->getValue();
        }

        if ($level == 0) {
            $return = '<' . '?xml version="1.0" encoding="UTF-8" standalone="yes"?' . '>' . "\n";
            $pre    = '';
        } else {
            $return = '';
            $pre    = $level > 0 ? str_repeat(' ', $level * 4) : '';
        }

        $return .= $pre . '<' . $this->getName();

        if ($this->hasAttributes())
        {
            foreach ($this->_attributes as $name => $value) {
                $return .= ' ' . $name . '="' . $this->_getCleanAttributeValue($value) . '"';
            }
        }

        if ($this->hasChildren() || $this->hasData())
        {
            $return .= '>';

            if ($this->hasChildren())
            {
                $return .= "\n";

                foreach ($this->_children as $entity) {
                    $return .= $entity->toString($level + 1) . "\n";
                }

                $return .= $pre . '</' . $this->getName() . '>';
            } else {
                $return .= $this->_getCleanData($this->_data);
                $return .= '</' . $this->getName() . '>';
            }
        } else {
            $return .= ' />';
        }

        return $return;
    }

    /**
     * Set element property
     *
     * @param string $name  property name
     * @param mixed  $value property value
     *
     * @return AeXml_Entity_Element self
     */
    public function set($name, $value)
    {
        if ($this->propertyExists($name, 'set')) {
            return parent::set($name, $value);
        }

        return $this->setAttribute($name, $value);
    }

    /**
     * Get element property
     *
     * @param string $name    property name
     * @param mixed  $default property default value
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if ($this->propertyExists($name)) {
            return parent::get($name, $default);
        }

        return $this->getAttribute($name, $default);
    }

    /**
     * Set element data
     *
     * @throws AeXmlEntityElementException #412 if element has child entities
     *
     * @param string $data
     *
     * @return AeXml_Entity_Element self
     */
    public function setData($data)
    {
        if ($this->hasChildren()) {
            throw new AeXmlEntityElementException('Cannot set data: element has child entities', 412);
        }

        return parent::setData($data);
    }

    /**
     * Write XML to file
     *
     * Writes XML to the specified file, using current element as a root
     * element. If you do not specify any file extension, xml is assumed
     *
     * @param string $file
     *
     * @return AeXml_Entity_Element self
     */
    public function save($file)
    {
        $file = (string) $file;

        if (strpos($file, '.') === false) {
            $file .= '.xml';
        }

        $file = AeFile::getInstance($file);

        if (!$file->exists()) {
            $file->create();
        }

        $file->write((string) $this);

        return $this;
    }

    protected function _getCleanAttributeValue($value)
    {
        $value = $this->_getCleanData($value);
        $value = str_replace('"', '&quot;', $value);

        return $value;
    }
}

/**
 * XML element exception class
 *
 * XML element-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeXmlEntityElementException extends AeXmlEntityException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Element');
        parent::__construct($message, $code);
    }
}
?>