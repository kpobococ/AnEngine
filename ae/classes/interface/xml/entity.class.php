<?php

interface AeInterface_Xml_Entity
{
    /**
     * Get entity data status
     *
     * @return bool
     */
    public function hasData();

    /**
     * Get entity data
     *
     * @param mixed $default default data to return
     *
     * @return string
     */
    public function getData($default = null);

    /**
     * Set entity data
     *
     * @param string $value
     *
     * @return AeInterface_Xml_Entity self
     */
    public function setData($data);

    /**
     * Get entity parent status
     *
     * Returns true if entity was added to another entity's children list, false
     * otherwise. This will also be false for any root elements.
     *
     * @return bool
     */
    public function hasParent();

    /**
     * Get entity parent
     *
     * Returns entity's parent element.
     *
     * @return AeInterface_Xml_Element parent element
     */
    public function getParent();

    /**
     * Set entity parent
     *
     * Sets the entity's parent element to <var>$element</var>.
     *
     * @param AeInterface_Xml_Element $element
     *
     * @return AeInterface_Xml_Entity self
     */
    public function setParent(AeInterface_Xml_Element $element);

    /**
     * Get entity position
     *
     * Returns current assigned position.
     *
     * @return int
     */
    public function getPosition();

    /**
     * Set entity position
     *
     * Relocates current entity to a <var>$position</var> inside the parent
     * element. Does not replace any entities, but simply reorders them
     * accordingly.
     *
     * @param int $position
     *
     * @return AeInterface_Xml_Entity self
     */
    public function setPosition($position);

    /**
     * Get previous sibling
     *
     * Returns null if there is no previous sibling
     *
     * If <var>$filter</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element. Using filter is only available with
     * instances of {@link AeInterface_Xml_Element}. Any data entities will be
     * skipped.
     *
     * @param string $name
     *
     * @return AeInterface_Xml_Entity
     */
    public function getPrevious($name = null);

    /**
     * Get next sibling
     *
     * Returns null if there is no next sibling
     *
     * If <var>$filter</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element. Using filter is only available with
     * instances of {@link AeInterface_Xml_Element}. Any data entities will be
     * skipped.
     *
     * @param string $name
     *
     * @return AeInterface_Xml_Entity
     */
    public function getNext($name = null);

    /**
     * Get entity type data status
     *
     * Returns true if entity is a data (text only) entity, false otherwise. A
     * data entity may only contain text and is used for mixed element contents
     * representation.
     *
     * @return bool
     */
    public function isData();

    /**
     * Get entity type element status
     *
     * Returns true if entity is an element, false otherwise. An element may
     * contain either data or other elements and is used for tag representation.
     *
     * @return bool
     */
    public function isElement();

    /**
     * Get formed XML
     *
     * Returns a well formed XML, treating current element as a root element. If
     * <var>$level</var> parameter is greater than 0, then there will be no XML
     * declaration and all elements will be prepended with 4 spaces per level.
     * This allows you to generate parts of XML data as you require.
     *
     * @param int $level element level
     *
     * @return string
     */
    public function toString($level = 0);
}
?>