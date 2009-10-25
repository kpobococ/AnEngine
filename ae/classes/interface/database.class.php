<?php
/**
 * Database interface file
 *
 * See {@link AeInterface_Database} interface documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */

/**
 * Database interface
 *
 * This is a common database driver interface. All database drivers must
 * implement it.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */
interface AeInterface_Database
{
    /**
     * Driver constructor
     *
     * This is to be used internally by {@link AeDatabase::getInstance()},
     * direct use is discouraged.
     *
     * If no connection data is provided, a connection is not established. It
     * should be established later using {@link AeInterface_Database::connect()}
     * method.
     *
     * Connection options, passed in the <var>$options</var> associative array,
     * may me different per database type. See driver documentation for a
     * detailed overview of all the options.
     *
     * @param string $username access username
     * @param string $password access password
     * @param array  $options  connection options
     */
    public function __construct($username = null, $password = null, $options = array());

    /**
     * Establish connection
     *
     * Establish a database connection using credentials and parameters passed.
     * See {@link AeInterface_Database::__construct()} for more information.
     *
     * @param string $username access username
     * @param string $password access password
     * @param array  $options  connection options
     *
     * @return bool true on success, false on failure
     */
    public function connect($username, $password, $options = array());

    /**
     * Terminate connection
     */
    public function disconnect();

    /**
     * Check if connected
     *
     * Check if a connection is currently established
     *
     * @return bool true if connected, false otherwise
     */
    public function isConnected();

    /**
     * Set query
     *
     * Set the query to be executed
     *
     * @param string $query target query
     * @param int    $limit number of results to show
     * @param int    $start result offset
     */
    public function setQuery($query, $limit = null, $start = 0);

    /**
     * Execute query
     *
     * Execute a previously set query
     *
     * @param array $data data to be bound to the statement (as seen in PDO's
     *                    prepare/execute statements)
     *
     * @return bool true if query was successful, false otherwise
     */
    public function execute($data = null);

    /**
     * Execute query and fetch rows
     *
     * This is a shortcut method for the following two calls:
     * <code> $db->setQuery($query);
     * $result = $db->getRows();</code>
     *
     * @param string $query target query
     *
     * @return array|bool an array of results or false if query failed
     */
    public function query($query);

    /**
     * Return last insert id
     *
     * @return int
     */
    public function getInsertId();

    /**
     * Return affected rows
     *
     * @return int
     */
    public function getAffectedRows();

    /**
     * Get result object
     *
     * Return first row of the result as an object
     *
     * @param string $class custom class name
     * @param array  $args  an array of arguments to pass to the class constructor
     *
     * @return object
     */
    public function getObject($class = 'stdClass', $args = null);

    /**
     * Get result array
     *
     * Return first row of the result as an associative array
     *
     * @return array
     */
    public function getRow();

    /**
     * Get result field
     *
     * Return a single field of the first result row
     *
     * @return string
     */
    public function getField();

    /**
     * Get result objects
     * 
     * Return all result rows as an array of objects. If <var>$key</var>
     * parameter is set, that field will be used as an index for the results
     * array
     * 
     * @param string $key   index field name
     * @param string $class custom class name
     * @param mixed  $args  an array of arguments to pass to the class constructor
     * 
     * @return array
     */
    public function getObjects($key = null, $class = 'stdClass', $args = null);

    /**
     * Get result rows
     *
     * Return all result rows as a two-dimensional array. If <var>$key</var>
     * parameter is set, that field will be used as an index for the results
     * array.
     *
     * @param string $key index field name
     *
     * @return array
     */
    public function getRows($key = null);

    /**
     * Get result fields
     *
     * Return a single result column as an array
     *
     * @return array
     */
    public function getFields();

    /**
     * Get query object
     *
     * Return a query assembly object
     *
     * @return AeDatabase_Query
     */
    public function queryObject();
}

?>