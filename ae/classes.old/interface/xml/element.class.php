<?php

interface AeInterface_Xml_Element extends AeInterface_Xml_Entity
{
    /**
     * Get element name
     *
     * @return string element name
     */
    public function getName();

    /**
     * Set element name
     *
     * @param string $name element name
     *
     * @return AeInterface_Xml_Element self
     */
    public function setName($name);

    /**
     * Get element attributes status
     *
     * @return bool true if element has attributes, false otherwise
     */
    public function hasAttributes();

    /**
     * Get element attributes
     *
     * @return array an associative array of attributes
     */
    public function getAttributes();

    /**
     * Set element attributes
     *
     * @param array $attributes an associative array of attributes
     *
     * @return AeInterface_Xml_Element self
     */
    public function setAttributes($attributes);

    /**
     * Get attribute status
     *
     * @param string $name attribute name
     *
     * @return bool true if attribute exists, false otherwise
     */
    public function hasAttribute($name);

    /**
     * Get attribute value
     *
     * @param string $name    attribute name
     * @param string $default attribute default value
     *
     * @return mixed attribute value
     */
    public function getAttribute($name, $default = null);

    /**
     * Set attribute value
     *
     * @param string $name  attribute name
     * @param string $value attribute value
     *
     * @return AeInterface_Xml_Element self
     */
    public function setAttribute($name, $value);

    /**
     * Get children status
     *
     * An optional <var>$name</var> parameter can be used to tell if an element
     * has child elements with <var>$name</var> tag name.
     *
     * @param string $name element name
     *
     * @return bool true if children found, false otherwise
     */
    public function hasChildren($name = null);

    /**
     * Get element children
     *
     * An optional <var>$name</var> parameter can be used to get child elements
     * with <var>$name</var> tag name.
     *
     * @param string $name element name
     *
     * @return array an array of {@link AeInterface_Xml_Entity}
     */
    public function getChildren($name = null);

    /**
     * Set element children
     *
     * @param array $children an array of {@link AeInterface_Xml_Entity}
     *
     * @return AeInterface_Xml_Element self
     */
    public function setChildren($children);

    /**
     * Get child status
     *
     * @param AeInterface_Xml_Entity|int $position either an integer position or
     *                                             AeInterface_Xml_Entity instance
     *
     * @return bool true if child is present, false otherwise
     */
    public function hasChild($position);

    /**
     * Get child element
     *
     * @param int $position an integer position
     *
     * @return AeInterface_Xml_Entity child entity
     */
    public function getChild($position);

    /**
     * Set child element
     *
     * Adds the <var>$child</var> entity as a child element at
     * <var>$position</var>. Does not replace any entities. Instead, an entity
     * at <var>$position</var> and all following entities are moved down.
     *
     * @param int                    $position an integer position
     * @param AeInterface_Xml_Entity $child    child entity
     *
     * @return AeInterface_Xml_Element self
     */
    public function setChild($position, AeInterface_Xml_Entity $child);

    /**
     * Get first child
     *
     * Returns null if there are no child elements
     *
     * If <var>$name</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element. Using filter is only available with
     * instances of {@link AeInterface_Xml_Element}. Any data entities will be
     * skipped.
     *
     * @param string $name element name
     *
     * @return AeInterface_Xml_Entity
     */
    public function getFirst($name = null);

    /**
     * Get last child
     *
     * Returns null if there are no child elements
     *
     * If <var>$name</var> parameter is set, it will be used as an element tag
     * name filter for a resulting element. Using filter is only available with
     * instances of {@link AeInterface_Xml_Element}. Any data entities will be
     * skipped.
     *
     * @param string $name element name
     *
     * @return AeInterface_Xml_Entity
     */
    public function getLast($name = null);

    /**
     * Add child entity
     *
     * @param AeInterface_Xml_Entity|string $child entity or tag name to create
     *
     * @return AeInterface_Xml_Entity added child
     */
    public function addChild($child);

    /**
     * Remove child entity
     *
     * @param AeInterface_Xml_Entity|int $child entity or position to remove
     *
     * @return AeInterface_Xml_Entity removed entity
     */
    public function removeChild($child);

    /**
     * Add child entity
     *
     * @param AeInterface_Xml_Entity|string $data entity or data to create
     *
     * @return AeInterface_Xml_Entity added child
     */
    public function addData($data);
}
?>