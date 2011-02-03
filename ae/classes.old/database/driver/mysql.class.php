<?php
/**
 * Database library MySQL driver file
 *
 * See {@link AeDatabase_Mysql} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Database library MySQL driver
 *
 * A general MySQL driver implementation
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeDatabase_Driver_Mysql extends AeDatabase_Driver
{
    protected $_driverName = 'mysql';

    /**
     * MySQL driver constructor
     *
     * This is to be used internally by {@link AeDatabase::getInstance()},
     * direct use is discouraged.
     *
     * If no connection data is provided, a connection is not established. It
     * should be established later using {@link AeDatabase_Mysql::connect()}
     * method.
     *
     * Connection options, passed in the <var>$options</var> associative array,
     * can be one or all of the following:
     * - host:       database host, default: localhost
     * - port:       database port, only used if host is specified, default: unspecified
     * - socket:     unix_socket, only used if host is NOT specified
     * - dbname:     database name, default: unspecified
     * - persistent: use persistent connection to the database, default: false
     * - errmode:    SILENT, WARNING or EXCEPTION, default: EXCEPTION
     *
     * @throws AeDatabaseMysqlException #401 if connection data is invalid
     *
     * @param string $username access username
     * @param string $password access password
     * @param array  $options  connection options
     */
    public function __construct($username = null, $password = null, $options = array())
    {
        try {
            parent::__construct($username, $password, $options);
        } catch (AeDatabaseDriverException $e) {
            throw new AeDatabaseDriverMysqlException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Establish connection
     *
     * Establish a database connection using credentials and parameters passed.
     * See {@link AeDatabase_Mysql::__construct()} for more information.
     *
     * The following options are currently supported:
     *  - persistent  bool    Request a persistent connection, rather than creating
     *                        a new connection. Defaults to false
     *  - timeout     int     Sets the timeout value in seconds for communications
     *                        with the database. Default to 0 (no timeout)
     *  - errmode     string  Either one of the following: silent, warning,
     *                        exception. Defines how PDO returns mysql errors.
     *                        Note, that exception mode is recommended, and is
     *                        default
     *
     * @throws AeDatabaseMysqlException #404 if PDO class not found
     * @throws AeDatabaseMysqlException #400 if connection data is invalid
     *
     * @param string $username access username
     * @param string $password access password
     * @param array  $options  PDO connection options
     *
     * @return bool true on success, false on failure
     */
    public function connect($username, $password, $options = array())
    {
        if (!class_exists('PDO', false)) {
            throw new AeDatabaseDriverMysqlException('PDO class not found', 404);
        }

        $dsn = 'mysql:';

        if (isset($options['host']))
        {
            $dsn .= 'host=' . $options['host'];

            if (isset($options['port'])) {
                $dsn .= ';port=' . $options['port'];
            }
        } else if (isset($options['socket'])) {
            $dsn .= 'unix_socket=' . $options['socket'];
        } else {
            $dsn .= 'host=localhost';
        }

        if (isset($options['dbname'])) {
            $dsn .= ';dbname=' . $options['dbname'];
        }

        $attrs = array();

        if (isset($options['persistent']) && $options['persistent'] == true) {
            $attrs[PDO::ATTR_PERSISTENT] = true;
        }

        if (isset($options['timeout']) && $options['timeout'] > 0) {
            $attrs[PDO::ATTR_TIMEOUT] = (int) $options['timeout'];
        }

        $mode = isset($options['errmode']) ? strtoupper($options['errmode']) : 'EXCEPTION';

        if (in_array($mode, array('SILENT', 'WARNING', 'EXCEPTION'))) {
            $attrs[PDO::ATTR_ERRMODE] = constant('PDO::ERRMODE_' . $mode);
        } else {
            $attrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }

        try {
            return parent::connect($username, $password, $attrs, $dsn);
        } catch (AeDatabaseDriverException $e) {
            throw new AeDatabaseDriverMysqlException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Set query
     *
     * Set the query to be executed
     *
     * @param string $query target query
     * @param int    $limit number of results to show
     * @param int    $start result offset
     */
    public function setQuery($query, $limit = null, $start = 0)
    {
        return parent::setQuery($query, $limit, $start);
    }
}

/**
 * Database MySQL driver exception class
 *
 * Database MySQL driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseDriverMysqlException extends AeDatabaseDriverException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Mysql');
        parent::__construct($message, $code);
    }
}

?>