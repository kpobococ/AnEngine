<?php
/**
 * Input class file
 *
 * See {@link AeInput} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Input class
 *
 *
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeInput extends AeNode_Nested
{
    /**
     * Use REQUEST container as source
     */
    const REQUEST = 1;

    /**
     * Use POST container as source
     */
    const POST = 2;

    /**
     * Use GET container as source
     */
    const GET = 3;

    /**
     * Currently used container
     * @var int
     */
    protected $_source = self::REQUEST;

    /**
     * Constructor
     *
     * @see AeInput::setSource()
     *
     * @throws AeInputException #400 if source value is invalid
     *
     * @param int $source one of source constants
     */
    public function __construct($source = null)
    {
        if (!is_null($source) && !$this->setSource($source)) {
            throw new AeInputException('Invalid source value: expecting null or integer, ' . AeType::of($source) . ' given', 400);
        }
    }

    /**
     * Set source
     *
     * @see AeInput::GET, AeInput::POST, AeInput::REQUEST
     *
     * @throws AeInputException #400 if an integer is out of supported bounds
     *
     * @param int $source one of source constants
     *
     * @return bool true on success, false otherwise
     */
    public function setSource($source)
    {
        if ($source instanceof AeType) {
            $source = $source->getValue();
        }

        if (!is_int($source)) {
            return false;
        }

        if ($source < self::REQUEST || $source > self::GET) {
            throw new AeInputException('Invalid source value: source not supported', 400);
        }

        if ($source == self::REQUEST) {
            // *** Request array is empty unless accessed
            isset($_REQUEST);
        }

        $this->_source = $source;

        return true;
    }

    /**
     * Get parameter
     *
     * Returns the <var>$name</var> parameter from the selected source. Returns
     * <var>$default</var> if <var>$name</var> parameter is not set:
     * <code> // *** Assuming $_GET = array('foo' => 'foo', 'baz' => array('bar' => 'bar'));
     * $input = new AeInput(AeInput::GET);
     * $input->get('foo', 'undefined'); // foo
     * $input->get('bar', 'undefined'); // undefined
     * $input->get('baz.bar', 'undefined'); // bar</code>
     *
     * This method does not return the default value, if the requested parameter
     * is set but empty, you should use {@link AeInput::getString() getString()}
     * method for this purpose.
     *
     * <b>NOTE:</b> This method is backwards compatible with AeNode_Nested::get(),
     * which may result in undesired return values. Use other getter methods to
     * overcome this effect:
     * <code> $input = new AeInput(AeInput::GET);
     * $_GET['source'] = 'foo';
     * $input->get('source'); // AeInput::GET value
     * $input->getString('source'); // foo</code>
     *
     * @see AeInput::getBoolean(), AeInput::getInteger(), AeInput::getFloat(),
     *      AeInput::getString(), AeInput::getText(), AeInput::getArray(),
     *      AeInput::GetHtml()
     *
     * @param string $name
     * @param mixed $default
     * 
     * @return AeType|mixed
     */
    public function get($name, $default = null)
    {
        $name = (string) $name;

        if ($this->propertyExists($name)) {
            return parent::get($name, $default);
        }

        return AeType::wrapReturn($this->_getClean($name), $default);
    }

    public function getBoolean($name, $default = false)
    {
        $value = $this->_getClean($name);

        if ($value == 'false') {
            $value = false;
        } else if ($value == 'true') {
            $value = true;
        }

        if ($value !== null) {
            $value = (bool) $value;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getInteger($name, $default = 0)
    {
        $value = $this->_getClean($name);

        if (is_numeric($value) && (int) $value == (float) $value) {
            $value = (int) $value;
        } else {
            $value = null;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getFloat($name, $default = 0.0)
    {
        $value = $this->_getClean($name);

        if (is_numeric($value)) {
            $value = (float) $value;
        } else {
            $value = null;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getString($name, $default = '')
    {
        $value = $this->_getClean($name);

        if (!empty($value))
        {
            $value = $this->_decodeString($value);
            $value = preg_replace('#\s+#', ' ', $value);
            $value = trim($value);
        } else {
            $value = null;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getText($name, $default = '')
    {
        $value = $this->_getClean($name);

        if (!empty($value))
        {
            $value = $this->_decodeString($value);
            $value = str_replace(array("\r\n", "\r"), "\n", $value);

            $lines = explode("\n", $value);

            foreach ($lines as $i => $line) {
                $lines[$i] = preg_replace('#\s+#', ' ', trim($line));
            }

            $value = implode("\n", $value);
        } else {
            $value = null;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getHtml($name, $default = '')
    {
        $value = $this->_getClean($name);

        if (!empty($value))
        {
            $value = $this->_decodeString($value);
            $value = str_replace(array("\r\n", "\r"), "\n", $value);
            $lines = explode("\n", $value);

            foreach ($lines as $i => $line) {
                $lines[$i] = preg_replace('#\s+#', ' ', trim($line));
            }

            $value = implode("\n", $value);
        } else {
            $value = null;
        }

        return new AeInput_Html($value, $default);
    }

    public function getArray($name, $default = array(), $callback = null)
    {
        $value = $this->_getClean($name);

        if (is_array($value))
        {
            if (!empty($callback))
            {
                if (AeType::of($callback) != 'string') {
                    throw new AeInputException('Invalid callback value: expecting string, ' . AeType::of($callback) . ' given', 400);
                }

                $callback = (string) $callback;
                $method   = 'get' . ucfirst($callback);

                if (!$this->methodExists($method)) {
                    throw new AeInputException('Invalid callback value: method not supported', 404);
                }

                foreach ($value as $k => $v)
                {
                    $key = $name . '.' . $k;

                    if (is_array($v)) {
                        $value[$k] = $this->getArray($key, $v, $callback);
                    } else {
                        $value[$k] = $this->call($method, array($key, $v));
                    }
                }
            }
        } else {
            $value = null;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function set($name, $value)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::set($name, $value);
        }

        return $this->_setByKey($name, $value, $GLOBALS[$this->_getSourceName()]);
    }

    public function clear($name)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set')) {
            return parent::clear($name);
        }

        return $this->_clearByKey($name, $GLOBALS[$this->_getSourceName()]);
    }

    protected function _decodeString($source)
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

    protected function _getSourceName()
    {
        if ($this->_source == self::POST) {
            return '_POST';
        }

        if ($this->_source == self::GET) {
            return '_GET';
        }

        return '_REQUEST';
    }

    protected function _getClean($name)
    {
        return $this->_clean($this->_getByKey($name, $GLOBALS[$this->_getSourceName()]));
    }

    /**
     * Clean recursively
     *
     * Returns a cleaned value, with its slashes stripped (strips recursively,
     * if value is an array), and null-bytes removed. All operations are
     * commited on both keys and values for arrays.
     *
     * Stripslashes is only performed, if magic quotes are enabled.
     *
     * @param string|array $value
     *
     * @return string|array
     */
    protected function _clean($value)
    {
        static $magicQuotes = null;

        if ($magicQuotes === null) {
            $magicQuotes = get_magic_quotes_gpc();
        }

        if (is_array($value))
        {
            $result = array();

            foreach ($value as $k => $v) {
                $result[$this->_clean($k)] = $this->_clean($v);
            }

            $value = $result;
        } else {
            if ($magicQuotes === 1) {
                $value = stripslashes($value);
            }

            // *** Removes possible null bytes
            $value = str_replace("\0", '', $value);
        }

        return $value;
    }
}

/**
 * Input exception class
 *
 * Input-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeInputException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Input');
        parent::__construct($message, $code);
    }
}
?>