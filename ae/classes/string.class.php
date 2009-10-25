<?php
/**
 * String class file
 *
 * See {@link AeString} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */

/**
 * String class
 *
 * This class is a replacement for php's generic string type. Made for
 * type-hinting and OOP-styled function call purposes.
 *
 * All the methods of this class support multibyte functions (iconv, and mb_string,
 * if the latter is present). The character encoding is always assumed to be
 * UTF-8, and there are no plans to make encoding configurable. See method
 * documentation for more details on multibyte support
 *
 * @method string getValue() getValue($default = null) Get a scalar string value
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework-Types
 */
class AeString extends AeScalar implements ArrayAccess
{
    /**
     * Scalar string value
     * @var string
     */
    protected $_value;

    /**
     * Strip the beginning of the string only
     */
    const TRIM_LEFT  = 1;

    /**
     * Strip the ending of the string only
     */
    const TRIM_RIGHT = 2;

    /**
     * Strip both the beginning and the ending of the string
     */
    const TRIM_BOTH  = 4;

    /**
     * Pad the string on the beginning
     */
    const PAD_LEFT  = STR_PAD_LEFT;

    /**
     * Pad the string on the ending
     */
    const PAD_RIGHT = STR_PAD_RIGHT;

    /**
     * Pad the string on both the beginning and the ending
     */
    const PAD_BOTH  = STR_PAD_BOTH;

    /**
     * Search the string from the beginning to the ending
     */
    const INDEX_LEFT  = 1;

    /**
     * Search the string from the ending to the beginning
     */
    const INDEX_RIGHT = 2;

    /**
     * Search the string from the beginning to the ending
     */
    const FROM_LEFT  = 1;

    /**
     * Search the string from the ending to the beginning
     */
    const FROM_RIGHT = 2;

    /**
     * Capitalize or minusculize first word only
     */
    const CONVERT_WORD = 1;

    /**
     * Capitalize or minusculize all words
     */
    const CONVERT_ALL  = 2;

    /**
     * String constructor
     *
     * @throws AeStringException #400 if the value passed is not a string
     *
     * @param string $value
     */
    public function __construct($value = null)
    {
        if (!is_null($value) && !$this->setValue($value)) {
            throw new AeStringException('Invalid value passed: expecting null or string, ' . AeType::typeOf($value) . ' given', 400);
        }
    }

    /**
     * Set a string value
     *
     * @param string $value
     *
     * @return bool true on valid value, false otherwise.
     */
    public function setValue($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } else {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return false;
        }

        $this->_value = $value;

        return true;
    }

    /**
     * Get character at index
     *
     * This method uses iconv functions to extract the character from a UTF-8
     * encoded multibyte string
     *
     * @see iconv_strlen(), iconv_substr()
     *
     * @param int|AeInteger   $index   index
     * @param string|AeString $default default value
     *
     * @return AeString
     */
    public function charAt($index, $default = null)
    {
        if ($index instanceof AeScalar) {
            $index = $index->toInteger()->getValue();
        }

        if ($default instanceof AeScalar) {
            $default = $default->toString()->charAt(0)->getValue();
        } else if (is_string($default)) {
            if (iconv_strlen($default, 'UTF-8')) {
                $default = iconv_substr($default, 0, 1, 'UTF-8');
            } else {
                $default = null;
            }
        }

        if (!is_null($this->getValue()) && $this->length() >= $index + 1) {
            $string = $this->getValue();
            return new AeString(iconv_substr($string, $index, 1, 'UTF-8'));
        }

        return new AeString($default);
    }

    /**
     * Strip whitespaces from the beginning and/or end of a string
     *
     * Trim mode can be one of the following:
     *  - {@link AeString::TRIM_LEFT}  - strip from the beginning
     *  - {@link AeString::TRIM_RIGHT} - strip from the end
     *  - {@link AeString::TRIM_BOTH}  - (default) strip from both beginning and end
     *
     * @see trim(), ltrim(), rtrim()
     *
     * @uses AeString::TRIM_LEFT
     * @uses AeString::TRIM_RIGHT
     * @uses AeString::TRIM_BOTH
     *
     * @param int $mode trim mode
     *
     * @return AeString
     */
    public function trim($mode = AeString::TRIM_BOTH)
    {
        switch ($mode)
        {
            case AeString::TRIM_LEFT: {
                return new AeString(ltrim($this->getValue()));
            } break;

            case AeString::TRIM_RIGHT: {
                return new AeString(rtrim($this->getValue()));
            } break;

            case AeString::TRIM_BOTH:
            default: {
                return new AeString(trim($this->getValue()));
            } break;
        }
    }

    /**
     * Pad a string to a length with another string
     *
     * Pad mode can be one of the following:
     *  - {@link AeString::PAD_LEFT}  - pad on the beginning
     *  - {@link AeString::PAD_RIGHT} - (default) pad on the end
     *  - {@link AeString::PAD_BOTH}  - pad on both beginning and end
     *
     * @see str_pad()
     *
     * @uses AeString::PAD_LEFT
     * @uses AeString::PAD_RIGHT
     * @uses AeString::PAD_BOTH
     *
     * @param int|AeInteger   $length
     * @param string|AeString $string
     * @param int             $mode
     *
     * @return AeString
     */
    public function pad($length, $string = ' ', $mode = AeString::PAD_RIGHT)
    {
        if ($length instanceof AeScalar) {
            $length = $length->toInteger()->getValue();
        }

        if ($length <= 0 || $length < $this->length()) {
            return $this;
        }

        if ($string instanceof AeScalar) {
            $string = $string->toString()->getValue();
        }

        // *** Use of regular strlen here is intentional
        if (strlen($string) == 0) {
            $string = ' ';
        }

        return new AeString(str_pad($this->getValue(), $length, $string, $mode));
    }

    /**
     * Repeat the string
     *
     * @see str_repeat()
     *
     * @throws AeStringException #400 if the value passed is less or equal to 0
     *
     * @param int|AeInteger $count number of times to repeat the string
     *
     * @return AeString
     */
    public function repeat($count)
    {
        if ($count instanceof AeScalar) {
            $count = $count->toInteger()->getValue();
        }

        if ($count <= 0) {
            throw new AeStringException('Count parameter must be greater than 0', 400);
        }

        return new AeString(str_repeat($this->getValue(), $count));
    }

    /**
     * Reverse the string
     *
     * This method loops through the whole string character by character, if a
     * string contains multibyte characters (detected using
     * {@link http://php.net/strlen strlen()} and
     * {@link http://php.net/iconv_strlen iconv_strlen()} return values
     * difference). This may take more time than expected on long strings
     *
     * @see strrev()
     *
     * @return AeString
     */
    public function reverse()
    {
        $length = $this->length();
        $strlen = strlen($this->getValue());

        if ($length != $strlen)
        {
            // *** Multibyte-safe reverse
            $string = '';

            for ($i = $length - 1; $i >= 0; $i--) {
                $string .= $this->charAt($i);
            }

            return new AeString($string);
        }

        return new AeString(strrev($this->getValue()));
    }

    /**
     * Replace one string with another string
     *
     * This implementation does not support array values for the <var>$search</var>
     * and <var>$replace</var> parameters
     *
     * @see str_replace()
     *
     * @param string|AeString $search
     * @param string|AeString $replace
     *
     * @return AeString
     */
    public function replace($search, $replace)
    {
        if ($search instanceof AeScalar) {
            $search = $search->toString()->getValue();
        }

        if ($replace instanceof AeScalar) {
            $replace = $replace->toString()->getValue();
        }

        return new AeString(str_replace($search, $replace, $this->getValue()));
    }

    /**
     * Return starting from first occurrence of a string
     *
     * Get mode can be one of the following:
     *  - {@link AeString::FROM_LEFT}  - (default) search from the beginning
     *  - {@link AeString::FROM_RIGHT} - search from the end
     *
     * Unlike the PHP's {@link strrchr()} function, this method searches for a
     * full <var>$needle</var>, if <var>$mode</var> is set to
     * {@link AeString::FROM_RIGHT}.
     *
     * This method uses mb_string functions, when available, to extract the
     * substring from a UTF-8 encoded multibyte string
     *
     * @see strstr(), stristr(), mb_strstr(), mb_stristr()
     *
     * @uses AeString::FROM_LEFT
     * @uses AeString::FROM_RIGHT
     *
     * @param string|AeString $needle
     * @param int             $mode
     * @param bool|AeBoolean  $case_sensitive
     *
     * @return AeString
     */
    public function getFrom($needle, $mode = AeString::FROM_LEFT, $case_sensitive = true)
    {
        if ($needle instanceof AeScalar) {
            $needle = $needle->toString()->getValue();
        }

        if ($mode == AeString::FROM_RIGHT) {
            return $this->part($this->indexOf($needle, 0, AeString::INDEX_RIGHT, $case_sensitive));
        }

        if ($case_sensitive === false)
        {
            if (function_exists('mb_stristr')) {
                return new AeString(mb_stristr($this->getValue(), $needle, false, 'UTF-8'));
            }

            return new AeString(stristr($this->getValue(), $needle));
        }

        if (function_exists('mb_strstr')) {
            return new AeString(mb_strstr($this->getValue(), $needle, false, 'UTF-8'));
        }

        return new AeString(strstr($this->getValue(), $needle));
    }

    /**
     * Return part of a string
     *
     * This method uses iconv functions, when available, to extract the
     * substring from a UTF-8 encoded multibyte string
     *
     * @see substr(), iconv_substr()
     *
     * @throws AeStringException #400 if the start value exceeds string length
     *
     * @param int|AeInteger $start
     * @param int|AeInteger $length
     *
     * @return AeString
     */
    public function part($start, $length = null)
    {
        if ($start instanceof AeScalar) {
            $start = $start->toInteger()->getValue();
        }

        if ($start >= $this->length()) {
            throw new AeStringException('Start value exceeds string length', 400);
        }

        if ($length instanceof AeScalar) {
            $length = $length->toInteger()->getValue();
        }

        if ($length === null) {
            return new AeString(iconv_substr($this->getValue(), $start, $this->length(), 'UTF-8'));
        }

        return new AeString(iconv_substr($this->getValue(), $start, $length, 'UTF-8'));
    }

    /**
     * Make the string lowercase
     *
     * This method uses mb_string functions, when available, to convert the case
     * of a UTF-8 encoded multibyte string
     *
     * @see AeString::toUpperCase(), AeString::toCamelCase(), AeString::capitalize(),
     *      AeString::minusculize(), AeString::hyphenate()
     *
     * @return AeString
     */
    public function toLowerCase()
    {
        if (function_exists('mb_strtolower')) {
            return new AeString(mb_strtolower($this->getValue(), 'UTF-8'));
        }

        return new AeString(strtolower($this->getValue()));
    }

    /**
     * Make the string uppercase
     *
     * This method uses mb_string functions, when available, to convert the case
     * of a UTF-8 encoded multibyte string
     *
     * @see AeString::toLowerCase(), AeString::toCamelCase(), AeString::capitalize(),
     *      AeString::minusculize(), AeString::hyphenate()
     *
     * @return AeString
     */
    public function toUpperCase()
    {
        if (function_exists('mb_strtoupper')) {
            return new AeString(mb_strtoupper($this->getValue(), 'UTF-8'));
        }

        return new AeString(strtoupper($this->getValue()));
    }

    /**
     * Make the string camelcase
     *
     * A camelcase string is a string where each word begins with a capital
     * letter with no spacings between words.
     *
     * This method searches for delimiter symbol between words and uses it to
     * find words. The supported symbols are: space, underscore, hyphen. Any and
     * all of them are considered word delimiters. This means, that any string,
     * containing more than one of these symbols, gets converted fully:
     * <code> $string = new AeString('my old-shcool word');
     * echo $string->toCamelCase(); // MyOldSchoolWord</code>
     *
     * You can add your own symbols to this list, by passing them as parameters
     * to the method:
     * <code> $string = new AeString('dot.delimited.string');
     * echo $string->toCamelCase('.'); // DotDelimitedString</code>
     *
     * If a passed delimiter is longer than 1 character, only the first character
     * is used.
     *
     * If a string contains uppercase characters somewhere in the middle of a word,
     * they are not converted to lowercase automatically:
     * <code> $string = new AeString('parse XML');
     * echo $string->toCamelCase(); // ParseXML</code>
     *
     * This method uses iconv and mb_string functions, when available, to
     * convert the case of a UTF-8 encoded multibyte string
     *
     * @see AeString::toUpperCase(), AeString::toLowerCase(), AeString::capitalize(),
     *      AeString::minusculize(), AeString::hyphenate()
     *
     * @param AeString|string $delimiter,... custom delimiter(s)
     *
     * @return AeString
     */
    public function toCamelCase()
    {
        $delims = array(' ', '_', '-');

        if (func_num_args() > 0)
        {
            // *** Custom delimiters passed
            $args = func_get_args();

            foreach ($args as $arg)
            {
                if (!is_string($arg) && !($arg instanceof AeString)) {
                    throw new AeStringException('Invalid delimiter value passed: expecting string, ' . gettype($arg) . ' given', 400);
                }

                $arg = (string) $arg;

                if (iconv_strlen($arg, 'UTF-8') > 1) {
                    $arg = iconv_substr($arg, 0, 1, 'UTF-8');
                }

                if (!in_array($arg, $delims)) {
                    $delims[] = $arg;
                }
            }
        }

        $delims = preg_quote(implode('', $delims));
        $string = preg_replace('#[' . $delims . ']+#u', ' ', $this->getValue());

        if (function_exists('mb_strtoupper'))
        {
            $string = explode(' ', $string);

            foreach ($string as $i => $part)
            {
                $length = iconv_strlen($part, 'UTF-8');

                if ($length > 0) {
                    $start      = iconv_substr($part, 0, 1, 'UTF-8');
                    $end        = iconv_substr($part, 1, $length, 'UTF-8');
                    $string[$i] = mb_strtoupper($start, 'UTF-8') . $end;
                }
            }

            return new AeString(implode('', $string));
        }

        $string = new AeString($string);

        return $string->split(' ')->walk('ucfirst')->join('');
    }

    /**
     * Make the string hyphenated
     *
     * A hyphenated string is a string where each word is separated by a hyphen:
     * <code> $string = new AeString('hello world');
     * echo $string->hyphenate(); // hello-world</code>
     *
     * You can also set your own word delimiter, using the <var>$delimiter</var>
     * parameter:
     * <code> $string = new AeString('hello world');
     * echo $string->hyphenate('_'); // hello_world</code>
     *
     * This method also converts camel case strings to hyphenated or otherwise
     * separated strings:
     * <code> $string = new AeString('CamelCaseString');
     * echo $string->hyphenate(); // camel-case-string
     * echo $string->hyphenate(' '); // camel case string</code>
     *
     * This method uses mb_string functions, when available, to convert the case
     * of a UTF-8 encoded multibyte string
     *
     * @see AeString::toUpperCase(), AeString::toLowerCase(), AeString::toCamelCase(),
     *      AeString::capitalize(), AeString::minusculize()
     *
     * @param AeString|string $delimiter
     *
     * @return AeString
     */
    public function hyphenate($delimiter = '-', $capitals = 'A-ZА-Я')
    {
        $delimiter = (string) $delimiter;
        $capitals  = (string) $capitals;
        $capitals  = preg_quote($capitals, '#');

        // *** Handle spaces
        if (strpos($this->getValue(), ' ')) {
            return $this->replace(' ', $delimiter);
        }

        $function = "strtolower('\\1')";

        if (function_exists('mb_strtolower')) {
            $function = "mb_strtolower('\\1', 'UTF-8')";
        }

        // *** Handle camel case
        $string = $this->minusculize()->getValue();
        $string = preg_replace('#([' . $capitals . '])#ue', "'" . $delimiter . "'." . $function, $string);

        return new AeString($string);
    }

    /**
     * Uppercase the first character of a string
     *
     * Capitalize mode can be one of the following:
     *  - {@link AeString::CONVERT_WORD}  - (default) capitalize first character only
     *  - {@link AeString::CONVERT_ALL}   - capitalize first character of each word
     *
     * This method uses mb_string functions, when available, to convert the case
     * of a UTF-8 encoded multibyte string
     *
     * @see AeString::toUpperCase(), AeString::toLowerCase(), AeString::toCamelCase(),
     *      AeString::minusculize(), AeString::hyphenate()
     *
     * @uses AeString::CONVERT_WORD
     * @uses AeString::CONVERT_ALL
     *
     * @param int $mode
     *
     * @return AeString
     */
    public function capitalize($mode = AeString::CONVERT_WORD)
    {
        if ($mode == AeString::CONVERT_ALL)
        {
            if (function_exists('mb_convert_case')) {
                return new AeString(mb_convert_case($this->getValue(), MB_CASE_TITLE, 'UTF-8'));
            }

            return new AeString(ucwords($this->getValue()));
        }

        if (function_exists('mb_strtoupper')) {
            return new AeString(mb_strtoupper($this->part(0, 1)->getValue(), 'UTF-8') . $this->part(1));
        }

        return new AeString(ucfirst($this->getValue()));
    }

    /**
     * Lowercase the first character of a string
     *
     * Minusculize mode can be one of the following:
     *  - {@link AeString::CONVERT_WORD}  - (default) minusculize first character only
     *  - {@link AeString::CONVERT_ALL}   - minusculize first character of each word
     *
     * This method uses iconv and mb_string functions, when available, to convert
     * the case of a UTF-8 encoded multibyte string
     *
     * @see AeString::toUpperCase(), AeString::toLowerCase(), AeString::toCamelCase(),
     *      AeString::capitalize(), AeString::hyphenate()
     *
     * @uses AeString::CONVERT_WORD
     * @uses AeString::CONVERT_ALL
     *
     * @param int $mode
     *
     * @return AeString
     */
    public function minusculize($mode = AeString::CONVERT_WORD)
    {
        if ($mode == AeString::CONVERT_ALL)
        {
            $string = $this->getValue();
            $words  = preg_split('#(\s+)#u', $string, -1, PREG_SPLIT_DELIM_CAPTURE);

            foreach ($words as $i => $word)
            {
                if (preg_match('#^\s+$#u', $word)) {
                    continue;
                }

                if (function_exists('mb_strtolower')) {
                    $length = iconv_strlen($word, 'UTF-8');
                    $start  = iconv_substr($word, 0, 1, 'UTF-8');
                    $end    = iconv_substr($word, 1, $length, 'UTF-8');
                    $word   = mb_strtolower($start, 'UTF-8') . $end;
                } else {
                    $word[0] = strtolower($word[0]);
                }

                $words[$i] = $word;
            }

            return new AeString(implode('', $words));
        }

        if (function_exists('mb_strtolower')) {
            $string = mb_strtolower($this->part(0, 1)->getValue(), 'UTF-8')
                    . $this->part(1)->getValue();
        } else {
            $string = strtolower($this->part(0, 1) . $this->part(1));
        }

        return new AeString($string);
    }

    /**
     * Return a formatted string
     *
     * {@link AeString} is used as a format string. Accepts unlimited number of
     * arguments
     *
     * @see sprintf()
     *
     * @param mixed|AeScalar $arg,...
     *
     * @return AeString
     */
    public function printf()
    {
        $args = func_get_args();

        foreach ($args as $i => $arg)
        {
            if ($arg instanceof AeScalar) {
                $args[$i] = $arg->getValue();
            }
        }

        array_unshift($args, $this->getValue());

        return new AeString(call_user_func_array('sprintf', $args));
    }

    /**
     * Parse a string according to a format
     *
     * @see sscanf()
     *
     * @param string|AeString $format
     *
     * @return AeArray
     */
    public function scanf($format)
    {
        if ($format instanceof AeScalar) {
            $format = $format->toString()->getValue();
        }

        return new AeArray(sscanf($this->getValue(), $format));
    }

    /**
     * Split the string by string
     *
     * @see explode()
     *
     * @throws AeStringException #400 if separator value is empty
     *
     * @param string|AeString $separator
     * @param int|AeInteger   $limit
     *
     * @return AeArray
     */
    public function split($separator, $limit = null)
    {
        if ($separator instanceof AeScalar) {
            $separator = $separator->toString()->getValue();
        }

        if ($separator === '') {
            throw new AeStringException('Separator can not be empty', 400);
        }

        if ($limit instanceof AeScalar) {
            $limit = $limit->toInteger()->getValue();
        }

        if (!is_null($limit)) {
            return new AeArray(explode($separator, $this->getValue(), $limit));
        }

        return new AeArray(explode($separator, $this->getValue()));
    }

    /**
     * Find position of a string
     *
     * IndexOf mode is one of the following:
     *  - {@link AeString::INDEX_LEFT}  - (default) search from left to right
     *  - {@link AeString::INDEX_RIGHT} - search from right to left
     *
     * This method returns scalar integer instead of {@link AeInteger} instance.
     * This is due to the fact, that the method does not manipulate the string
     * in any way but is informational
     *
     * This method uses iconv and mb_string functions, when available, to detect
     * the index of a needle in a UTF-8 encoded multibyte string
     *
     * @see strpos(), stripos(), strrpost(), strripos()
     *
     * @uses AeString::INDEX_LEFT
     * @uses AeString::INDEX_RIGHT
     *
     * @param string|AeString $needle
     * @param int|AeInteger   $offset
     * @param int             $mode
     * @param bool|AeBoolean  $case_sensitive
     *
     * @return int
     */
    public function indexOf($needle, $offset = 0, $mode = AeString::INDEX_LEFT, $case_sensitive = true)
    {
        if ($needle instanceof AeScalar) {
            $needle = $needle->toString()->getValue();
        }

        if ($offset instanceof AeScalar) {
            $offset = $offset->toInteger()->getValue();
        }

        if ($case_sensitive instanceof AeScalar) {
            $case_sensitive = $case_sensitive->toBoolean()->getValue();
        }

        if ($mode == AeString::INDEX_RIGHT)
        {
            if ($case_sensitive === false)
            {
                if (function_exists('mb_strripos')) {
                    return mb_strripos($this->getValue(), $needle, $offset, 'UTF-8');
                }

                return strripos($this->getValue(), $needle, $offset);
            }

            if ($offset !== 0)
            {
                if (function_exists('mb_strrpos')) {
                    // *** Use mb_string function instead
                    return mb_strrpos($this->getValue(), $needle, $offset, 'UTF-8');
                }

                // *** Emulate $offset parameter for iconv_strrpos
                if ($offset > 0) {
                    $string = iconv_substr($this->getValue(), $offset, $this->length(), 'UTF-8');
                } else {
                    $string = iconv_substr($this->getValue(), 0, $offset, 'UTF-8');
                    $offset = 0;
                }

                return iconv_strrpos($string, $needle, 'UTF-8') + $offset;
            }

            return iconv_strrpos($this->getValue(), $needle, 'UTF-8');
        }
        
        if ($case_sensitive === false)
        {
            if (function_exists('mb_stripos')) {
                return mb_stripos($this->getValue(), $needle, $offset, 'UTF-8');
            }

            return stripos($this->getValue(), $needle, $offset);
        }

        return iconv_strpos($this->getValue(), $needle, $offset, 'UTF-8');
    }

    /**
     * Return string length
     *
     * This method returns scalar integer instead of {@link AeInteger} instance.
     * This is due to the fact, that the method does not manipulate the string
     * in any way but is informational.
     *
     * This method uses iconv functions to detect the length of a UTF-8 encoded
     * multibyte string
     *
     * @return int
     */
    public function length()
    {
        return iconv_strlen($this->getValue(), 'UTF-8');
    }

    /**
     * String type cast support method
     *
     * This method is called every time an object is being cast to string (i.e.
     * echoed).
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getValue('null');
    }

    /**
     * Whether an offset exists
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @param int|AeInteger $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->toInteger()->getValue();
        }

        if (!is_int($offset)) {
            return false;
        }

        return $this->length() > $offset;
    }

    /**
     * Return offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @param int|AeInteger $offset
     *
     * @return AeString
     */
    public function offsetGet($offset)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->toInteger()->getValue();
        }

        if (!is_int($offset) || !$this->offsetExists($offset)) {
            return null;
        }

        return $this->charAt($offset);
    }

    /**
     * Set offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @throws AeStringException #400 if offset is invalid
     *
     * @param int|AeInteger   $offset
     * @param string|AeString $value
     */
    public function offsetSet($offset, $value)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->toInteger()->getValue();
        }

        if (!is_int($offset) || !$this->offsetExists($offset)) {
            throw new AeStringException('Invalid offset value', 400);
        }

        if ($value instanceof AeScalar) {
            $value = $value->toString()->getValue();
        }

        $value = (string) $value;

        if (iconv_strlen($value, 'UTF-8') > 1) {
            $value = iconv_substr($value, 0, 1, 'UTF-8');
        }

        $string = $this->part(0, $offset)->getValue()
                . $value
                . $this->part($offset + 1)->getValue();

        $this->setValue($string);
    }

    /**
     * Unset offset value
     *
     * Method for the {@link ArrayAccess} interface implementation
     *
     * @throws AeStringException #400 if offset is invalid
     *
     * @param int|AeInteger $offset
     */
    public function offsetUnset($offset)
    {
        if ($offset instanceof AeScalar) {
            $offset = $offset->toInteger()->getValue();
        }

        if (!is_int($offset) || !$this->offsetExists($offset)) {
            throw new AeStringException('Invalid offset value', 400);
        }

        $this->setValue($this->part(0, $offset)->getValue() . $this->part($offset + 1)->getValue());
    }
}

/**
 * String exception class
 *
 * String-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeStringException extends AeScalarException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('String');
        parent::__construct($message, $code);
    }
}
?>