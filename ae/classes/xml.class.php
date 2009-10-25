<?php
/**
 * XML class file
 *
 * See {@link AeXml} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * XML class
 *
 * This is an XML parser class. It uses PHP's XML Parser functions to parse an
 * XML file into a tree of nodes.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeXml
{
    const DEFAULT_DRIVER = 'simple';

    /**
     * Get XML parser instance
     *
     * @throws AeXmlException #404 if driver not found
     * @throws AeXmlException #501 if driver is not an implementation of
     *                             the {@link AeInterface_Xml} interface
     *
     * @param string $driver  driver name
     * @param mixed  $arg,... unlimited number of arguments to pass to the driver
     *
     * @return AeInterface_Xml instance of a selected parser driver
     */
    public static function getInstance($driver = null)
    {
        $driver = $driver !== null ? $driver : self::DEFAULT_DRIVER;
        $class  = 'AeXml_Driver_' . ucfirst($driver);
        $args   = func_get_args();
        $args   = array_splice($args, 1);

        try {
            $instance = AeInstance::get($class, $args, true, true);
        } catch (AeInstanceException $e) {
            if ($e->getCode() == 404) {
                throw new AeXmlException(ucfirst($driver) . ' driver not found', 404);
            }

            throw $e;
        }

        if (!($instance instanceof AeInterface_Xml)) {
            throw new AeXmlException(ucfirst($driver) . ' driver has an invalid access interface', 501);
        }

        return $instance;
    }

    /**
     * Parse XML file
     *
     * Gets an XML parser driver and parses selected file.
     *
     * @param string $file
     * @param string $driver
     *
     * @return AeXml_Node
     */
    public static function getContents($file, $driver = null)
    {
        if (!strpos($file, '.')) {
            $file .= '.xml';
        }

        if (!file_exists($file)) {
            throw new AeXmlException('File does not exist', 404);
        }

        $parser = self::getInstance($driver, file_get_contents($file));

        return $parser->parse();
    }

    /**
     * Create XML node
     *
     * Creates and returns a root XML Element with the parameters specified. Use
     * this method to start creating your own XML structure to be written to a
     * file.
     *
     * @param string $name element name
     *
     * @return AeXml_Node
     */
    public static function node($name)
    {
        return new AeXml_Node($name);
    }
}

/**
 * XML exception class
 *
 * XML-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeXmlException extends AeLibraryException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Xml');
        parent::__construct($message, $code);
    }
}
?>