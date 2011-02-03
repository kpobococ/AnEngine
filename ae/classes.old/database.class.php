<?php
/**
 * Database library file
 *
 * See {@link AeDatabase} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Database library
 *
 * This is a basic database wrapper. It uses PDO as it's base. The logic behind
 * this decision is simple: PDO provides a common interface for a number of
 * database platforms. It is also a compiled PHP extension, which means it would
 * be faster than AdoDB etc.
 *
 * You can use any of the following methods to get a database wrapper object:
 * <code> $db = AeDatabase::getInstance('mysql');
 *
 * // *** Most configuration options set explicitly
 * $db = AeDatabase::getInstance('mysql', 'username', 'password', array(
 *     'host'       => 'localhost',
 *     'dbname'     => 'my_database',
 *     'persistent' => true,
 *     'errmode'    => 'EXCEPTION'
 * ));
 *
 * // *** Direct class call, removing an extra parameter
 * $db = AeDatabase::getInstance('mysql', 'username', 'password', array('dbname' => 'my_database'));
 *
 * // *** Load default connection configuration from ae/database.ini file
 * $db = AeDatabase::getConnection();
 *
 * // *** Load custom connection configuration from ae/database.ini file
 * $db = AeDatabase::getConnection('readonly');</code>
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeDatabase
{
    const DEFAULT_DRIVER     = 'mysql';
    const DEFAULT_CONNECTION = 'default';

    /**
     * Get database object
     *
     * @throws AeDatabaseException #404 if driver not found
     * @throws AeDatabaseException #501 if driver is not an implementation of
     *                             the {@link AeInterface_Database} interface
     *
     * @param string $driver  driver name
     * @param mixed  $arg,... unlimited number of arguments to pass to the driver
     *
     * @return AeInterface_Database instance of a selected database driver
     */
    public static function getInstance($driver = null)
    {
        $driver = $driver !== null ? $driver : self::DEFAULT_DRIVER;
        $class  = 'AeDatabase_Driver_' . ucfirst($driver);
        $args   = func_get_args();
        $args   = array_slice($args, 1);

        try {
            $instance = AeInstance::get($class, $args, true, true);
        } catch (AeInstanceException $e) {
            if ($e->getCode() == 404) {
                throw new AeDatabaseException(ucfirst($driver) . ' driver not found', 404);
            }

            throw $e;
        }

        if (!($instance instanceof AeInterface_Database)) {
            throw new AeDatabaseException(ucfirst($driver) . ' driver has an invalid access interface', 501);
        }

        return $instance;
    }

    /**
     * Get database connection
     *
     * Get the database connection, using parameters from the settings file to
     * connect. The optional second argument may be an instance of {@link
     * AeInterface_File} or a path to configuration file, which will be opened
     * using {@link AeSettings}. If it is ommited, a database.ini file is
     * assumed, which should reside in the current working directory (see {@link
     * http://php.net/getcwd getcwd()} function) or anywhere else inside the
     * include path (see {@link http://php.net/get_include_path
     * get_include_path() function}.
     *
     * @throws AeDatabaseException #500 if connection fails due to bad
     *                             configuration
     *
     * @param string                      $name     name of the connection
     * @param string|AeInterface_Settings $settings custom configuration file
     *
     * @return AeInterface_Database instance of a database driver
     */
    public static function getConnection($name = null, $settings = null)
    {
        $name = $name !== null ? $name : self::DEFAULT_CONNECTION;

        if (!($settings instanceof AeInterface_Settings))
        {
            if ($settings === null || !file_exists($settings))
            {
                $file = getcwd() . SLASH . 'database.ini';

                if (!file_exists($file)) {
                    $file = 'database.ini';
                }
            } else {
                $file = $settings;
            }

            $settings = AeSettings::getInstance($file);
        }

        $driver   = $settings->get($name.'.driver'  , self::DEFAULT_DRIVER);
        $username = $settings->get($name.'.user'    , 'root');
        $password = $settings->get($name.'.password', '');
        $options  = $settings->get($name.'.options' , array());

        $connection = self::getInstance($driver, $username, $password, $options);

        return $connection;
    }

    /**
     * Replace prefix
     * 
     * Replace table placeholder prefixes, defined in <var>$mask</var> with the
     * actual table prefix specified in <var>$prefix</var>.
     *
     * @param string $query  target query
     * @param string $prefix
     * @param string $mask
     *
     * @return string
     */
    public static function replacePrefix($query, $prefix, $mask = '#__')
    {
        $query     = trim((string) $query);
        $escaped   = false;
        $quoteChar = '';
        $n         = strlen($query);
        $startPos  = 0;
        $return    = '';

        while ($startPos < $n)
        {
            $ip = strpos($query, $mask, $startPos);

            if ($ip === false) {
                break;
            }

            $j = strpos($query, "'", $startPos);
            $k = strpos($query, '"', $startPos);

            if (($k !== false) && (($k < $j) || ($j === false))) {
                $quoteChar = '"';
                $j         = $k;
            } else {
                $quoteChar = "'";
            }

            if ($j === false) {
                $j = $n;
            }

            $return .= str_replace($mask, $prefix, substr($query, $startPos, $j - $startPos));

            $startPos = $j;
            $j        = $startPos + 1;

            if ($j >= $n) {
                break;
            }

            // quote comes first, find end of quote
            while (true)
            {
                $k       = strpos($query, $quoteChar, $j);
                $escaped = false;

                if ($k === false) {
                    break;
                }

                $l = $k - 1;

                while ($l >= 0 && $query{$l} == '\\') {
                    $l--;
                    $escaped = !$escaped;
                }

                if ($escaped) {
                    $j = $k + 1;
                    continue;
                }

                break;
            }

            if ($k === false) {
                // error in the query - no end quote; ignore it
                break;
            }

            $return .= substr($query, $startPos, $k - $startPos + 1);

            $startPos = $k+1;
        }

        if ($startPos < $n) {
            $return .= substr($query, $startPos, $n - $startPos);
        }

        return $return;
    }
}

/**
 * Database exception class
 *
 * Database-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Database');
        parent::__construct($message, $code);
    }
}

?>