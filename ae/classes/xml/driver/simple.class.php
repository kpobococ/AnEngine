<?php
/**
 * Simple XML parser driver file
 *
 * See {@link AeXml_Driver_Simple} class documentation.
 *
 * @requires xml
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Simple XML parser driver
 *
 * This XML parser uses PHP's XML Parser functions to parse an XML file into a
 * tree of {@link AeXml_Node AeXml_Nodes}.
 * 
 * This parser requires PHP's XML library to be installed. This library comes
 * enabled by default, but may be disabled when compiling PHP manually with a
 * certain command line option.
 *
 * @requires xml
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeXml_Driver_Simple extends AeXml_Driver
{
    /**
     * Parser resource
     * @var resource
     */
    private $_parser;

    /**
     * Result root node
     * @var AeXml_Node
     */
    private $_result = null;

    /**
     * Current position node
     * @var AeXml_Node
     */
    private $_position = array();

    /**
     * Get parsed data
     *
     * Parses an XML file and retuns parsed content wrappen into an {@link
     * AeXml_Node} class instance.
     *
     * @throws AeXmlDriverSimpleException #404 if XML Parser functions are not
     *                                         available
     * @throws AeXmlDriverSimpleException #500 on XML Parser error
     *
     * @return AeXml_Node
     */
    public function parse()
    {
        if (!function_exists('xml_parser_create')) {
            throw new AeXmlDriverSimpleException('PHP XML Parser functions are not available', 404);
        }

        $this->_parser = xml_parser_create('UTF-8');

        xml_set_object($this->_parser, $this);
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);

        // *** Set parse handlers
        xml_set_element_handler($this->_parser, 'startElement', 'endElement');
        xml_set_character_data_handler($this->_parser, 'characterData');

        if (!xml_parse($this->_parser, $this->getSource()))
        {
            $code = xml_get_error_code($this->_parser);
            $text = xml_error_string($code);

            if ($code == 9) {
                $text .= ' (Line '      . xml_get_current_line_number($this->_parser)
                      .  ', character ' . xml_get_current_column_number($this->_parser) . ')';
            }

            throw new AeXmlDriverSimpleException($text, 500);
        }

        $return        = $this->_result;
        $this->_result = null;

        return $return;
    }

    /**
     * Element start handler
     *
     * This method handles elements' opening tags
     *
     * @param resource $parser     XML Parser
     * @param string   $name       element tag name
     * @param array    $properties element properties
     *
     * @return bool
     */
    public function startElement($parser, $name, $properties = array())
    {
        $level = count($this->_position);

        if ($level == 0) {
            // *** Root element
            $this->_result   = AeXml::node($name);
            $this->_position = $this->_result;
        } else {
            $this->_position = $this->_position->addChild($name);
        }

        $this->_position->properties = $properties;

        return true;
    }

    /**
     * Element end handler
     *
     * This method handles elements' closing tags
     *
     * @param resource $parser XML Parser
     * @param string   $name   element tag name
     */
    public function endElement($parser, $name)
    {
        $this->_position = $this->_position->parent;

        return true;
    }

    /**
     * Element character data handler
     *
     * This method handles elements' character data
     *
     * @param resource $parser XML Parser
     * @param string   $data   element's character data
     *
     * @return bool
     */
    public function characterData($parser, $data)
    {
        if (trim($data) == '') {
            return false;
        }

        return $this->_position->setData($this->_position->getData('') . $data);
    }
}

/**
 * Simple XML parser driver exception class
 *
 * Simple XML parser driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeXmlDriverSimpleException extends AeXmlDriverException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Simple');
        parent::__construct($message, $code);
    }
}
?>