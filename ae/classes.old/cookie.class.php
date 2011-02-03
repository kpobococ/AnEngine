<?php
/**
 * Cookie class file
 *
 * See {@link AeCookie} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Cookie class
 *
 * This class allows an easier management of cookies and various cookie
 * operations.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeCookie
{
    /**
     * Default cookie domain
     * @var string
     */
    protected static $_domain = null;

    /**
     * Default cookie path
     * @var string
     */
    protected static $_path = null;

    /**
     * Default cookie secure flag
     * @var bool
     */
    protected static $_secure = false;

    /**
     * Default cookie httponly flag
     * @var bool
     */
    protected static $_http = false;

    /**
     * Set cookie
     *
     * Sets the cookie to the value specified. Any missing parameters are taken
     * from the current default values ({@link AeCookie::$_path}, {@link
     * AeCookie::$_domain}, {@link AeCookie::$_secure} and {@link
     * AeCookie::$_http} respectively)
     *
     * @param string $name   cookie name
     * @param string $value  cookie value
     * @param int    $expire cookie expire date. Defaults to 0, which makes the
     *                       cookie expire as soon as the browser is closed
     * @param string $path   cookie path. Defaults to /
     * @param string $domain cookie domain. Defaults to current domain
     * @param bool   $secure if this is true, the cookie can only be set using a
     *                       secure connection (https)
     * @param bool   $http   if this is true, the cookie will be made accessible
     *                       only through the HTTP protocol. This means that the
     *                       cookie won't be accessible by scripting languages,
     *                       such as JavaScript. This setting can effectively
     *                       help to reduce identity theft through XSS attacks
     *                       (although it is not supported by all browsers).
     *                       Requires PHP 5.2.0
     *
     * @return bool true on success, false otherwise
     */
    public static function set($name, $value, $expire = null, $path = null, $domain = null, $secure = null, $http = null)
    {
        if (headers_sent()) {
            return false;
        }

        if ($expire instanceof AeScalar) {
            $expire = $expire->toInteger()->value;
        }

        if ($secure instanceof AeScalar) {
            $secure = $secure->toBoolean()->value;
        }

        if ($http instanceof AeScalar) {
            $http = $http->toBoolean()->value;
        }

        $expire = $expire === null ? 0              : (int) $expire;
        $path   = $path   === null ? self::$_path   : (string) $path;
        $domain = $domain === null ? self::$_domain : (string) $domain;
        $secure = $secure === null ? self::$_secure : (bool) $secure;
        $http   = $http   === null ? self::$_http   : (bool) $http;
        $args   = array((string) $name, (string) $value, $expire);

        if ($path === null) {
            $path = '/';
        }

        if ($domain === null) {
            $domain = $_SERVER['SERVER_NAME'];
        }

        $args[] = $path;
        $args[] = $domain;
        $args[] = $secure;

        if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
            $args[] = $http;
        }

        return @call_user_func_array('setcookie', $args);
    }

    /**
     * Get cookie value
     *
     * Returns the value of the cookie, identified by <var>$name</var> parameter,
     * or <var>$default</var>, if that cookie is not set. Also clears the cookie
     * value, removing any null bytes and slashes, added by magic quotes (if the
     * latter setting is enabled).
     *
     * The return value is wrapped inside the respective wrapper class. The
     * default value is not wrapped
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return AeArray|AeScalar
     */
    public static function get($name, $default)
    {
        if (!isset($_COOKIE[(string) $name])) {
            return $default;
        }

        $value = self::_tidy($_COOKIE[(string) $name]);

        if (is_array($value)) {
            return new AeArray($value);
        }

        return AeScalar::wrap($value);
    }

    /**
     * Clear cookie value
     *
     * Removes the cookie for the user by setting the expiration time to a time
     * in the past. All the parameters are the same as for the {@link
     * AeCookie::set()} method. This means, that a cookie will only be unset for
     * a certain path, domain and secure level
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     *
     * @return bool
     */
    public static function clear($name, $path = null, $domain = null, $secure = null)
    {
        return self::set($name, '', time() - 42000, $path, $domain, $secure);
    }

    /**
     * Set default domain
     *
     * Sets the default cookie domain to use with the {@link AeCookie::set()}
     * method
     *
     * @param string $domain
     *
     * @return string previous default domain value or true, if no value was set
     *                before
     */
    public static function setDomain($domain)
    {
        $return = true;

        if (self::$_domain !== null) {
            $return = self::$_domain;
        }

        self::$_domain = (string) $domain;

        return $return;
    }

    /**
     * Set default path
     *
     * Sets the default cookie path to use with the {@link AeCookie::set()}
     * method
     *
     * @param string $path
     *
     * @return string previous default path value or true, if no value was set
     *                before
     */
    public static function setPath($path)
    {
        $return = true;

        if (self::$_path !== null) {
            $return = self::$_path;
        }

        self::$_path = (string) $path;

        return $return;
    }

    /**
     * Set default secure
     *
     * Sets the default cookie secure flag to use with the {@link
     * AeCookie::set()} method
     *
     * @param bool $secure
     *
     * @return bool
     */
    public static function setSecure($secure)
    {
        if ($secure instanceof AeScalar) {
            $secure = $secure->toBoolean()->value;
        }

        self::$_secure = (bool) $secure;

        return true;
    }

    /**
     * Set default httponly
     *
     * Sets the default cookie httponly flag to use with the {@link
     * AeCookie::set()} method
     *
     * @param bool $http
     *
     * @return bool
     */
    public static function setHttp($http)
    {
        if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
            return false;
        }

        if ($http instanceof AeScalar) {
            $http = $http->toBoolean()->value;
        }

        self::$_http = (bool) $http;

        return true;
    }

    /**
     * Set default options
     *
     * Sets the default cookie options to use with the {@link AeCookie::set()}
     * method. This is a shortcut to setting all the default options one by one
     *
     * @param array $options
     *
     * @return bool
     */
    public static function setOptions($options)
    {
        if ($options instanceof AeArray) {
            $options = $options->value;
        }

        if (isset($options['path'])) {
            self::setPath($options['path']);
        }

        if (isset($options['domain'])) {
            self::setDomain($options['domain']);
        }

        if (isset($options['secure'])) {
            self::setSecure($options['secure']);
        }

        if (isset($options['http'])) {
            self::setHttp($options['http']);
        }

        return true;
    }

    /**
     * Get default options
     *
     * Returns an array of the default cookie options. These options are path,
     * domain, secure and http. The http option is only present in the return
     * value, if the current PHP version is 5.2.0 or later
     *
     * @return AeArray
     */
    public static function getOptions()
    {
        $return = new AeArray(array(
            'path'   => self::getPath(),
            'domain' => self::getDomain(),
            'secure' => self::getSecure()
        ));

        if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
            $return['http'] = self::getHttp();
        }

        return $return;
    }

    /**
     * Get default domain
     *
     * Returns the default domain or <var>$default</var>, if the default domain
     * is not set
     *
     * @param mixed $default
     *
     * @return AeString the default value is not wrapped
     */
    public static function getDomain($default = null)
    {
        return self::$_domain === null ? $default : new AeString(self::$_domain);
    }

    /**
     * Get default path
     *
     * Returns the default path or <var>$default</var>, if the default path is
     * not set
     *
     * @param mixed $default
     *
     * @return AeString the default value is not wrapped
     */
    public static function getPath($default = null)
    {
        return self::$_path === null ? $default : new AeString(self::$_path);
    }

    /**
     * Get default secure
     *
     * Returns the default secure or <var>$default</var>, if the default secure
     * is not set
     *
     * @param mixed $default
     *
     * @return AeBoolean the default value is not wrapped
     */
    public static function getSecure($default = null)
    {
        return self::$_secure === null ? $default : new AeBoolean(self::$_secure);
    }

    /**
     * Get default httponly
     * 
     * Returns the default httponly or <var>$default</var>, if the default
     * httponly is not set.
     *
     * Returns false, if current PHP version is lower than 5.2.0
     *
     * @param mixed $default
     *
     * @return AeBoolean the default value is not wrapped
     */
    public static function getHttp($default = null)
    {
        if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
            return false;
        }

        return self::$_http === null ? $default : new AeBoolean(self::$_http);
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
    protected static function _tidy($value)
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
 * Cookie exception class
 *
 * Cookie-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeCookieException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Cookie');
        parent::__construct($message, $code);
    }
}
?>