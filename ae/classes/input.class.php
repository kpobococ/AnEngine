<?php
/**
 * Input class file
 *
 * See {@link AeInput} class documentation.
 *
 * @todo rewrite class to be a child of AeNode_Nested
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Input class
 *
 * This is the framework's basic input filtering class. The basic getter method
 * returns a value wrapped into a {@link AeString} class instance, no additional
 * filtering is done. Other methods filter content depending on the target
 * result:
 * <code> // Let's assume that we have the following values: GET['foo'] = 'bar',
 * // POST['foo'] = 'bar12', REQUEST['foo'] = 'bar12';
 * echo AeInput::get('foo'); // bar12
 * echo AeInput::get('foo', null, AeInput::GET); // bar
 * echo AeInput::get('bar', 'baz'); // baz
 *
 * echo AeInput::getInteger('foo'); // 12
 * echo AeInput::getInteger('foo', null, AeInput::GET); // 0</code>
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeInput
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

    /**
     * Class name
     *
     * This is useful if you plan on extending the static functionality of the
     * class: it allows to redirect all the static method calls from inside this
     * class methods to your extending class:
     * <code> class MyInput extends AeInput
     * {
     *     // ...
     * }
     *
     * AeInput::$self = 'MyInput';</code>
     *
     * @var string
     */
    public static $self = 'AeInput';

    /**
     * Get boolean value
     *
     * See the {@link AeInput::get() get()} method for detailed parameter
     * documentation
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeBoolean|AeArray
     */
    public static function getBoolean($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        return AeType::wrapReturn((bool) $value);
    }

    /**
     * Get integer value
     *
     * See the {@link AeInput::get() get()} method for detailed parameter
     * documentation
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeInteger
     */
    public static function getInteger($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        return AeType::wrapReturn((int) $value);
    }

    /**
     * Get float value
     *
     * See the {@link AeInput::get() get()} method for detailed parameter
     * documentation
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeFloat
     */
    public static function getFloat($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        return AeType::wrapReturn((float) $value);
    }

    /**
     * Get path value
     *
     * The path value is a string value, wrapped into an instance of the
     * {@link AeString} class. The value will only contain alphanumeric
     * characters, underscores, hyphens and slashes:
     * <code> $pattern = '/^[A-Z0-9_-]+[A-Z0-9_\.-]*(?:[\\\\\/][A-Z0-9_-]+[A-Z0-9_\.-]*)*$/i';</code>
     *
     * See the {@link AeInput::get() get()} method for detailed parameter
     * documentation
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeString
     */
    public static function getPath($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        $pattern = '/^[A-Z0-9_-]+[A-Z0-9_\.-]*(?:[\\\\\/][A-Z0-9_-]+[A-Z0-9_\.-]*)*$/i';
        $matches = array();

        preg_match($pattern, $value, $matches);

        $value = (string) @$matches[0];

        return AeType::wrapReturn($value);
    }

    /**
     * Get command value
     *
     * The command value can only contain alphanumeric characters, underscores,
     * hyphens and dots, and cannot start with a dot.
     *
     * See the {@link AeInput::get() get()} method for detailed parameter
     * documentation
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeString
     */
    public static function getCommand($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        $value = preg_replace('#[^A-Z0-9_\.-]#i', '', $value);
        $value = ltrim($value, '.');

        return AeType::wrapReturn($value);
    }

    /**
     * Get string value
     *
     * A string value will have all newlines and other spacing characters
     * converted to a single space, the whole value trimmed and several special
     * html characters replaced to generate valid XHTML output:
     * <code> $_REQUEST['foo'] = " foo \n\t &bar\r\n\t\t\t<b>baz</b>";
     * var_dump(AeInput::getString('foo'); // outputs "foo &amp;bar &lt;b&gt;baz&lt;/b&gt;"</code>
     *
     * See the {@link AeInput::get() get()} method for detailed parameter
     * documentation
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeString
     */
    public static function getString($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        $filter = self::_getFilter();
        $value  = $filter->decode($value);
        $value  = preg_replace('#[\s]+#', ' ', $value);
        $value  = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
        $value  = trim($value);

        return AeType::wrapReturn((string) $value);
    }

    /**
     * Get text value
     *
     * A text value will have all win-style line breaks replaced by nix-style
     * line breaks, all other spacing characters converted to a single space,
     * bad tags stripped, the whole value trimmed (except line breaks) and
     * several special html characters replaced to generate valid XHTML output:
     * <code> $_REQUEST['foo'] = " foo \n\t &bar\r\n\t\t\t<b>baz</b>";
     * var_dump(AeInput::getString('foo'); // outputs "foo\n&amp;bar\n<b>baz</b>"</code>
     *
     * @see AeInput_Filter::remove()
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeString
     */
    public static function getText($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        $filter = self::_getFilter();
        $value  = $filter->remove($filter->decode($value));
        $value  = str_replace('&', '&amp;', $value);
        $lines  = explode("\n", $value);

        foreach ($lines as $i => $line) {
            $lines[$i] = preg_replace('#[\s]+#', ' ', $lines[$i]);
            $lines[$i] = trim($lines[$i]);
        }

        $value = implode("\n", $lines);

        return AeType::wrapReturn($value);
    }

    /**
     * Get array value
     *
     * See the {@link AeInput::get() get()} method for detailed parameter
     * documentation
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeArray
     */
    public static function getArray($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        if (!is_array($value)) {
            $value = (array) $value;
        }

        $filter = self::_getFilter();

        foreach ($value as $k => $v)
        {
            // *** Filter element for XSS and other 'bad' code etc.
            if (is_string($v)) {
                $value[$k] = $filter->remove($filter->decode($v));
            }
        }

        return AeType::wrapReturn($value);
    }

    /**
     * Basic getter method
     *
     * Returns a value wrapped into an {@link AeString} class instance with no
     * additional filtering done.
     *
     * The <var>$array</var> value can be one of the following:
     *  - {@link AeInput::REQUEST} - (default) use the REQUEST superglobal
     *  - {@link AeInput::POST}    - use the POST superglobal
     *  - {@link AeInput::GET}     - use the GET superglobal
     *
     * @param string $name    parameter name
     * @param mixed  $default parameter default value
     * @param int    $array   the container array to use
     *
     * @return AeString
     */
    public static function get($name, $default = null, $array = AeInput::REQUEST)
    {
        $value = call_user_func(array(self::$self, '_get'), $name, null, $array);

        if ($value === null) {
            return AeType::wrapReturn($default);
        }

        return AeType::wrapReturn($value);
    }

    public static function exists($name, $array)
    {
        $name = (string) $name;

        switch ($array)
        {
            case AeInput::POST: {
                return isset($_POST[$name]);
            } break;

            case AeInput::GET: {
                return isset($_GET[$name]);
            } break;

            case AeInput::REQUEST: {
                return isset($_REQUEST[$name]);
            } break;
        }

        return false;
    }

    /**
     * Get input filter
     *
     * Returns an instance of the {@link AeInput_Filter} class. This method is
     * used mostly to be able to override the settings of the filter in custom
     * applications
     *
     * @return AeInput_Filter
     */
    protected static function _getFilter()
    {
        return new AeInput_Filter;
    }

    /**
     * Get basic value
     *
     * Returns the basic value, either string or an array, filtered of any
     * slashes, if necessary.
     *
     * @param string $name
     * @param mixed  $default
     * @param int    $array
     *
     * @return string|array
     */
    protected static function _get($name, $default, $array)
    {
        switch ($array)
        {
            case AeInput::POST:
            {
                if (!isset($_POST[$name]) || $_POST[$name] == '') {
                    return $default;
                }

                $value = $_POST[$name];
            } break;

            case AeInput::GET:
            {
                if (!isset($_GET[$name]) || $_GET[$name] == '') {
                    return $default;
                }

                $value = $_GET[$name];
            } break;

            case AeInput::REQUEST:
            {
                if (!isset($_REQUEST[$name]) || $_REQUEST[$name] == '') {
                    return $default;
                }

                $value = $_REQUEST[$name];
            } break;

            default: {
                throw new AeInputException('Container not supported', 405);
            } break;
        }

        // *** Tidy the value
        $value = self::_tidy($value);

        return $value;
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
                $result[self::_tidy($k)] = self::_tidy($v);
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