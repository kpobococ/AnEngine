<?php
/**
 * Xml interface file
 *
 * See {@link AeInterface_Xml} interface documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */

/**
 * Xml interface
 *
 * This is a common xml parser interface. All xml parser drivers must implement
 * it.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */
interface AeInterface_Xml
{
    /**
     * Constructor
     *
     * @param string $file file to parse
     */
    public function __construct($file = null);

    /**
     * Set source
     *
     * @param string $source
     *
     * @return bool
     */
    public function setSource($source);

    /**
     * Parse file
     *
     * @return AeXml_Node
     */
    public function parse();
}
?>