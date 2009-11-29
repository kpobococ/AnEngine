<?php

abstract class AeXml_Entity extends AeObject implements AeInterface_Xml_Entity
{
    /**
     * Entity data
     * @var string
     */
    protected $_data = null;

    /**
     * Entity parent
     *
     * This is null for entities with no parent
     *
     * @var AeInterface_Xml_Element
     */
    protected $_parent = null;

    /**
     * Entity position
     *
     * This indicates the entity's position in an array of its parent's child
     * entities.
     *
     * This is null for entities with no parent
     *
     * @var int
     */
    protected $_position = null;

    public function hasData()
    {
        return !is_null($this->_data);
    }

    public function getData($default = null)
    {
        return $this->hasData() ? $this->_data : $default;
    }

    public function setData($data)
    {
        $this->_data = (string) $data;

        return $this;
    }

    public function hasParent()
    {
        return !is_null($this->_parent);
    }

    /**
     * Get entity parent
     *
     * Returns entity's parent element.
     *
     * @throws AeXmlEntityException #404 if parent not found
     *
     * @return AeInterface_Xml_Element parent element
     */
    public function getParent()
    {
        if (!$this->hasParent()) {
            throw new AeXmlEntityException('Cannot get parent: parent not found', 404);
        }

        return $this->_parent;
    }

    public function setParent(AeInterface_Xml_Element $element)
    {
        // *** Remove current parent first
        if ($this->hasParent()) {
            $this->_parent->removeChild($this);
        }

        // *** Add entity as element's child
        $this->_parent = $element;

        return $this;
    }

    /**
     * Get entity position
     *
     * Returns current assigned position.
     *
     * @throws AeXmlEntityException #412 if no parent was assigned
     *
     * @return int
     */
    public function getPosition()
    {
        if (!$this->hasParent()) {
            throw new AeXmlEntityException('Cannot get position: no parent assigned', 412);
        }

        return $this->_position;
    }

    /**
     * Set entity position
     *
     * Sets the entity's position to <var>$position</var>. If null is passed,
     * entity position is reset and parent is unassigned.
     *
     * @throws AeXmlEntityException #400 on invalid position value
     * @throws AeXmlEntityException #412 if no parent was assigned
     *
     * @param int|null $position
     *
     * @return AeInterface_Xml_Entity self
     */
    public function setPosition($position)
    {
        if ($position instanceof AeType) {
            $position = $position->getValue();
        }

        $type = AeType::of($position);

        if ($type != 'null')
        {
            if ($type != 'integer') {
                throw new AeXmlEntityException('Invalid value passed: expecting integer, ' . $type . ' given', 400);
            }

            if ($position < 0) {
                throw new AeXmlEntityException('Invalid value passed: position cannot be less than zero', 400);
            }

            if (!$this->hasParent()) {
                throw new AeXmlEntityException('Cannot set position: no parent assigned', 412);
            }
        } else {
            $this->_parent   = null;
        }

        $this->_position = $position;

        return $this;
    }

    public function getPrevious($name = null)
    {
        if ($this->_position == 0) {
            return null;
        }

        if (is_null($name)) {
            return $this->_parent->getChild($this->_position - 1);
        }

        $children = $this->_parent->getChildren();
        $name     = $this->_getCleanName($name);

        for ($i = 0; $i < $this->_position; $i ++)
        {
            if ($children[$i] instanceof AeInterface_Xml_Element && $children[$i]->getName() == $name) {
                return $children[$i];
            }
        }

        return null;
    }

    public function getNext($name = null)
    {
        $children = $this->_parent->getChildren();
        $length   = count($children);

        if ($this->_position == $length - 1) {
            return null;
        }

        if (is_null($name)) {
            return $this->_parent->getChild($this->_position + 1);
        }

        $name = $this->_getCleanName($name);

        for ($i = $this->_position + 1; $i < $length; $i++)
        {
            if ($children[$i] instanceof AeInterface_Xml_Element && $children[$i]->getName() == $name) {
                return $children[$i];
            }
        }

        return null;
    }

    public function isData()
    {
        return !$this->isElement();
    }

    public function isElement()
    {
        return ($this instanceof AeInterface_Xml_Element);
    }

    /**
     * Get clean data
     *
     * Returns element data, escaped for safe writing or outputting.
     *
     * @return string
     */
    protected function _getCleanData($data)
    {
        $data = str_replace('&', '&amp;', $data);
        $data = str_replace('<', '&lt;' , $data);
        $data = str_replace('>', '&gt;' , $data);

        return $data;
    }

    protected function _getCleanName($name)
    {
        $name = trim((string) $name);
        $name = preg_replace('#[^-a-zA-Z0-9:\._·]#', '', $name);
        $name = preg_replace('#^[^a-zA-Z:_]+#', '', $name);

        return $name;
    }

    public function __clone()
    {
        $this->_parent   = null;
        $this->_position = null;

        if ($this instanceof AeInterface_Xml_Element && $this->hasChildren())
        {
            $children        = $this->_children;
            $this->_children = array();

            foreach ($children as $child) {
                $this->addChild(clone $child);
            }
        }
    }
}

/**
 * XML entity exception class
 *
 * XML entity-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeXmlEntityException extends AeXmlException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Entity');
        parent::__construct($message, $code);
    }
}
?>