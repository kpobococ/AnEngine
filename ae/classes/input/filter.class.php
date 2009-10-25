<?php
/**
 * Input filter class file
 *
 * See {@link AeInput_Filter} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Input filter class
 *
 * This is the actual input filtering class.
 *
 * Forked from the php input filter library by: Daniel Morris <dan@rootcube.com>
 *
 * Original Contributors: Gianpaolo Racca, Ghislain Picard, Marco Wandschneider,
 *                        Chris Tobin and Andrew Eddie.
 *
 * @author Daniel Morris <dan@rootcube.com>
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeInput_Filter extends AeObject
{
    protected $_tags;
    protected $_attributes;

    protected $_tagsMethod;
    protected $_attributesMethod;

    protected $_xssAuto;

    protected $_blacklistTags       = array('applet', 'body', 'bgsound', 'base', 'basefont', 'embed', 'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name', 'object', 'script', 'style', 'title', 'xml');
    protected $_blacklistAttributes = array('action', 'background', 'codebase', 'dynsrc', 'lowsrc');

    const METHOD_ALLOW = 0;
    const METHOD_DENY  = 1;

    /**
     * Constructor
     *
     * Tags and attributes method can be one of the following:
     *  - {@link AeInput_Filter::METHOD_ALLOW} - allow listed tags/attributes
     *  - {@link AeInput_Filter::METHOD_DENY}  - deny listed tags/attributes
     *
     * @param array $tags             list of user-defined tags
	 * @param array $attributes       list of user-defined attributes
	 * @param int   $tagsMethod       user-defined tags method
	 * @param int   $attributesMethod user-defined attributes method
	 * @param int   $xssAuto          false - only auto clean essentials
     *                                true  - allow clean blacklisted tags/attr
     */
    public function __construct($tags = array(), $attributes = array(), $tagsMethod = AeInput_Filter::METHOD_ALLOW, $attributesMethod = AeInput_Filter::METHOD_ALLOW, $xssAuto = true)
    {
        // Make sure user defined arrays are in lowercase
        $tags       = array_map('strtolower', (array) $tags);
        $attributes = array_map('strtolower', (array) $attributes);

        // Assign member variables
        $this->_tags             = $tags;
        $this->_attributes       = $attributes;
        $this->_tagsMethod       = $tagsMethod;
        $this->_attributesMethod = $attributesMethod;
        $this->_xssAuto          = $xssAuto;
    }

    /**
     * Function to determine if contents of an attribute is safe
	 *
	 * @param   array   $attrSubSet	a 2 element array for attributes name, value
     *
	 * @return  boolean true if bad code is detected, false otherwise
     */
    protected function _checkAttribute($attrSubSet)
    {
        $attrSubSet[0] = strtolower($attrSubSet[0]);
        $attrSubSet[1] = strtolower($attrSubSet[1]);

        return (((strpos($attrSubSet[1], 'expression') !== false) && ($attrSubSet[0]) == 'style') || (strpos($attrSubSet[1], 'javascript:') !== false) || (strpos($attrSubSet[1], 'behaviour:') !== false) || (strpos($attrSubSet[1], 'vbscript:') !== false) || (strpos($attrSubSet[1], 'mocha:') !== false) || (strpos($attrSubSet[1], 'livescript:') !== false));
    }

    /**
	 * Remove all unwanted tags and attributes
	 *
	 * @param string $source input string to be cleaned
     *
	 * @return string cleaned version of input string
	 */
    public function remove($source)
    {
        $loopCounter = 0;

        // TODO: rewrite the whole thing, currently all tags are stripped

        // *** Iteration provides nested tag protection
        while ($source != ($cleaned = $this->_cleanTags($source))) {
            $source = $cleaned;
            $loopCounter ++;
        }

        return $source;
    }

    /**
	 * Internal method to strip a string of certain tags
	 *
	 * @param string $source Input string to be cleaned
     *
	 * @return string Cleaned version of input string
	 */
    protected function _cleanTags($source)
    {
        $preTag		  = null;
        $postTag	  = $source;
        $currentSpace = false;
        $attr         = '';

        $tagOpen_start	= strpos($source, '<');

        while ($tagOpen_start !== false)
        {
            // *** Get some information about the tag we are processing
            $preTag      .= substr($postTag, 0, $tagOpen_start);
            $postTag      = substr($postTag, $tagOpen_start);
            $fromTagOpen  = substr($postTag, 1);
            $tagOpen_end  = strpos($fromTagOpen, '>');

            // *** Let's catch any non-terminated tags and skip over them
            if ($tagOpen_end === false) {
                $postTag       = substr($postTag, $tagOpen_start + 1);
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }

            // *** Do we have a nested tag?
            $tagOpen_nested     = strpos($fromTagOpen, '<');
            $tagOpen_nested_end	= strpos(substr($postTag, $tagOpen_end), '>');

            if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag        .= substr($postTag, 0, ($tagOpen_nested + 1));
                $postTag        = substr($postTag, ($tagOpen_nested + 1));
                $tagOpen_start  = strpos($postTag, '<');
                continue;
            }

            // *** Lets get some information about our tag and setup attribute pairs
            $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
            $currentTag     = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength      = strlen($currentTag);
            $tagLeft        = $currentTag;
            $attrSet        = array();
            $currentSpace   = strpos($tagLeft, ' ');

            // *** Are we an open tag or a close tag?
            if (substr($currentTag, 0, 1) == '/') {
                // *** Close Tag
                $isCloseTag    = true;
                list($tagName) = explode(' ', $currentTag);
                $tagName       = substr($tagName, 1);
            } else {
                // *** Open Tag
                $isCloseTag    = false;
                list($tagName) = explode(' ', $currentTag);
            }

            /*
             * Exclude all "non-regular" tagnames
             * OR no tagname
             * OR remove if xssauto is on and tag is blacklisted
             */
            if ((!preg_match("/^[a-z][a-z0-9]*$/i", $tagName)) || !$tagName || (in_array(strtolower($tagName), $this->_blacklistTags) && $this->_xssAuto)) {
                $postTag       = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');
                // *** Strip tag
                continue;
            }

            /*
             * Time to grab any attributes from the tag... need this section in
             * case attributes have spaces in the values.
             */
            while ($currentSpace !== false)
            {
                $attr        = '';
                $fromSpace   = substr($tagLeft, ($currentSpace + 1));
                $nextSpace   = strpos($fromSpace, ' ');
                $openQuotes  = strpos($fromSpace, '"');
                $closeQuotes = strpos(substr($fromSpace, ($openQuotes + 1)), '"') + $openQuotes + 1;

                // *** Do we have an attribute to process? [check for equal sign]
                if (strpos($fromSpace, '=') !== false)
                {
                    /*
                     * If the attribute value is wrapped in quotes we need to
                     * grab the substring from the closing quote, otherwise grab
                     * till the next space
                     */
                    if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes + 1)), '"') !== false)) {
                        $attr = substr($fromSpace, 0, ($closeQuotes + 1));
                    } else {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                } else {
                    /*
                     * No more equal signs so add any extra text in the tag into
                     * the attribute array [eg. checked]
                     */
                    if ($fromSpace != '/') {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                }

                // *** Last Attribute Pair
                if (!$attr && $fromSpace != '/') {
                    $attr = $fromSpace;
                }

                // *** Add attribute pair to the attribute array
                $attrSet[] = $attr;

                // *** Move search point and continue iteration
                $tagLeft      = substr($fromSpace, strlen($attr));
                $currentSpace = strpos($tagLeft, ' ');
            }

            // *** Is our tag in the user input array?
            $tagFound = in_array(strtolower($tagName), $this->_tags);

            // *** If the tag is allowed lets append it to the output string
            if ((!$tagFound && $this->_tagsMethod) || ($tagFound && !$this->_tagsMethod))
            {
                // *** Reconstruct tag with allowed attributes
                if (!$isCloseTag)
                {
                    // *** Open or Single tag
                    $attrSet  = $this->_cleanAttributes($attrSet);
                    $preTag  .= '<' . $tagName;

                    for ($i = 0; $i < count($attrSet); $i++) {
                        $preTag .= ' ' . $attrSet[$i];
                    }

                    // *** Reformat single tags to XHTML
                    if (strpos($fromTagOpen, '</' . $tagName)) {
                        $preTag .= '>';
                    } else {
                        $preTag .= ' />';
                    }
                } else {
                    // *** Closing Tag
                    $preTag .= '</' . $tagName . '>';
                }
            }

            // *** Find next tag's start and continue iteration
            $postTag       = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');
        }

        // *** Append any code after the end of tags and return
        if ($postTag != '<') {
            $preTag .= $postTag;
        }

        return $preTag;
    }

    /**
     * Internal method to strip a tag of certain attributes
     *
     * @param array $attrSet Array of attribute pairs to filter
     *
     * @return array Filtered array of attribute pairs
     */
    protected function _cleanAttributes($attrSet)
    {
        // *** Initialize variables
        $newSet = array();

        // *** Iterate through attribute pairs
        for ($i = 0; $i < count($attrSet); $i ++)
        {
            // *** Skip spaces
            if (!$attrSet[$i]) {
                continue;
            }

            // *** Split into name/value pairs
            $attrSubSet = explode('=', trim($attrSet[$i]), 2);
            list($attrSubSet[0]) = explode(' ', $attrSubSet[0]);

            /*
             * Remove all "non-regular" attribute names
             * AND blacklisted attributes
             */
            if ((!preg_match('/[a-z]*$/i', $attrSubSet[0])) || (($this->_xssAuto) && ((in_array(strtolower($attrSubSet[0]), $this->_blacklistAttributes)) || (substr($attrSubSet[0], 0, 2) == 'on')))) {
                continue;
            }

            // *** XSS attribute value filtering
            if ($attrSubSet[1])
            {
                // *** strips unicode, hex, etc
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                // *** strip normal newline within attr value
                $attrSubSet[1] = preg_replace('/[\n\r]/', '', $attrSubSet[1]);
                // *** strip double quotes
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);

                // *** convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr value)
                if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'")) {
                    $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
                }

                // *** strip slashes
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }

            // *** Autostrip script tags
            if ($this->_checkAttribute($attrSubSet)) {
                continue;
            }

            // *** Is our attribute in the user input array?
            $attrFound = in_array(strtolower($attrSubSet[0]), $this->_attributes);

            // *** If the tag is allowed lets keep it
            if ((!$attrFound && $this->_attributesMethod) || ($attrFound && !$this->_attributesMethod))
            {
                // *** Does the attribute have a value?
                if ($attrSubSet[1]) {
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
                } elseif ($attrSubSet[1] == "0") {
                    /*
                     * Special Case
                     * Is the value 0?
                     */
                    $newSet[] = $attrSubSet[0] . '="0"';
                } else {
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
                }
            }
        }

        return $newSet;
    }

    /**
	 * Try to convert to plaintext
	 *
	 * @param string $source
     *
	 * @return string Plaintext string
	 */
    public function decode($source)
    {
        // *** Entity decode
        $table = get_html_translation_table(HTML_ENTITIES);

        foreach ($table as $k => $v) {
            $map[$v] = utf8_encode($k);
        }

        $source = strtr($source, $map);

        // *** Convert decimal
        $source = preg_replace('/&#(\d+);/me', "utf8_encode(chr(\\1))", $source);

        // *** Convert hex
        $source = preg_replace('/&#x([a-f0-9]+);/mei', "utf8_encode(chr(0x\\1))", $source);

        return $source;
    }
}

/**
 * Input filter exception class
 *
 * Input-filter specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeInputFilterException extends AeInputException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Filter');
        parent::__construct($message, $code);
    }
}
?>