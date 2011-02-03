<?php
/**
 * XML parser driver file
 *
 * See {@link AeXml_Driver} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * XML parser driver
 *
 * An XML parser driver handles the parsing of XML files.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeXml_Driver extends AeObject implements AeInterface_Xml
{
    /**
     * Source file path
     * @var string
     */
    protected $_source;

    /**
     * Constructor
     *
     * @param string $file
     */
    public function __construct($source = null)
    {
        if (!is_null($source) && !$this->setSource($source)) {
            throw new AeXmlDriverException('Source is invalid', 404);
        }
    }

    /**
     * Set source
     *
     * @param string $source source data
     *
     * @return bool
     */
    public function setSource($source)
    {
        $this->_source = (string) $source;

        return true;
    }
}

/**
 * XML parser driver exception class
 *
 * XML parser driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeXmlDriverException extends AeXmlException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Driver');
        parent::__construct($message, $code);
    }
}
?>