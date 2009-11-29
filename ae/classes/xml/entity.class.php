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
        $element->addChild($this);

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
     * Relocates current entity to a <var>$position</var> inside the parent
     * element. Does not replace any entities, but simply reorders them
     * accordingly.
     *
     * @throws AeXmlEntityException #400 on invalid position value
     * @throws AeXmlEntityException #412 if no parent was assigned
     *
     * @param int $position
     *
     * @return AeInterface_Xml_Entity self
     */
    public function setPosition($position)
    {
        $type = AeType::of($position);

        if ($type != 'integer') {
            throw new AeXmlEntityException('Invalid value passed: expecting integer, ' . $type . ' given', 400);
        }

        if ($position instanceof AeInteger) {
            $position = $position->getValue();
        }

        if ($position < 0) {
            throw new AeXmlEntityException('Invalid value passed: position cannot be less than zero', 400);
        }

        if (!$this->hasParent()) {
            throw new AeXmlEntityException('Cannot set position: no parent assigned', 412);
        }

        if ($position != $this->_position)
        {
            $parent = $this->_parent;
            $length = count($parent->getChildren());

            if ($position >= $length) {
                throw new AeXmlEntityException('Invalid value passed: position must be less than ' . $length, 400);
            }

            $parent->removeChild($this);
            $parent->setChild($position, $this);
        }

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