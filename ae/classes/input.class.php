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
 * @todo add getText method with tag filtering
 * @todo add getArray method with callback support
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeInput extends AeNode_Nested
{
    /**
     * Use REQUEST container
     */
    const REQUEST = 1;

    /**
     * Use POST container
     */
    const POST = 2;

    /**
     * Use GET container
     */
    const GET = 3;

    protected $_source = self::REQUEST;

    public function __construct($source = null)
    {
        if (!is_null($source) && !$this->setSource($source)) {
            throw new AeInputException('Invalid source value: expecting null or integer, ' . AeType::typeOf($source) . ' given', 400);
        }
    }

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

        $this->_source = $source;

        return true;
    }

    public function get($name, $default = null)
    {
        $name = (string) $name;

        if ($this->propertyExists($name)) {
            return parent::get($name, $default);
        }

        return AeType::wrapReturn($this->_getByKey($name, $GLOBALS[$this->_getSourceName()]), $default);
    }

    public function getBoolean($name, $default = false)
    {
        $value = $this->_getByKey($name, $GLOBALS[$this->_getSourceName()]);

        if ($value == 'false') {
            $value = false;
        }

        if ($value !== null) {
            $value = (bool) $value;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getInteger($name, $default = 0)
    {
        $value = $this->_getByKey($name, $GLOBALS[$this->_getSourceName()]);

        if (is_numeric($value) && (int) $value == (float) $value) {
            $value = (int) $value;
        } else {
            $value = null;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getFloat($name, $default = 0.0)
    {
        $value = $this->_getByKey($name, $GLOBALS[$this->_getSourceName()]);

        if (is_numeric($value)) {
            $value = (float) $value;
        } else {
            $value = null;
        }

        return AeType::wrapReturn($value, $default);
    }

    public function getString($name, $default = null)
    {
        $value = $this->_getByKey($name, $GLOBALS[$this->_getSourceName()]);

        if (!is_empty($value)) {
            $value = $this->_decodeString($value);
            $value = preg_replace('#[\s]+#', ' ', $value);

            $value = trim($value);
        }

        return AeType::wrapReturn((string) $value);
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

    /**
     * Tidy recursively
     *
     * Returns a tidied value, with its slashes stripped (strips recursively, if
     * value is an array), and null-bytes removed. All operations are commited
     * on both keys and values for arrays.
     *
     * Stripslashes is only performed, if magic quotes are enabled.
     *
     * @param string|array $value
     *
     * @return string|array
     */
    protected function _tidy($value)
    {
        static $magicQuotes = null;

        if ($magicQuotes === null) {
            $magicQuotes = get_magic_quotes_gpc();
        }

        if (is_array($value))
        {
            $result = array();

            foreach ($value as $k => $v) {
                $result[$this->_tidy($k)] = $this->_tidy($v);
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