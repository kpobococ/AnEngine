<?php
/**
 * Database library driver file
 *
 * See {@link AeDatabase_Driver} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Database library driver
 *
 * A database driver class simplifies an implementation of specific drivers by
 * implementing the basic logic in all applicable methods. It is recommended to
 * derive all specific database driver classes from this class. However, it is
 * only required to implement the {@link AeInterface_Database} interface for any
 * custom database driver.
 *
 * If a custom driver is not an implementation of the {@link
 * AeInterface_Database}, an exception will be thrown by {@link AeDatabase}. See
 * {@link AeDatabase::getInstance()} for more details.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeDatabase_Driver extends AeObject implements AeInterface_Database
{
    /**
     * Instance of {@link PDO} class
     * @var object PDO
     */
    protected $_db = null;

    /**
     * Current table prefix
     * @var string
     */
    protected $_prefix = null;

    /**
     * Current query
     * @var string
     */
    protected $_query = null;

    /**
     * Instance of {@link PDOStatement} class representing current result set
     * @var object PDOStatement
     */
    protected $_result = null;

    /**
     * Driver name
     * @var string
     */
    protected $_driverName = null;

    /**
     * Driver constructor
     *
     * This is to be used internally by {@link AeDatabase::getInstance()},
     * direct use is discouraged.
     *
     * If no connection data is provided, a connection is not established. It
     * should be established later using {@link AeDatabase_Driver::connect()}
     * method.
     *
     * Connection options, passed in the <var>$options</var> associative array,
     * may me different per database type. See driver documentation for a
     * detailed overview of all the options.
     *
     * @throws AeDatabaseDriverException #401 if connection data is invalid
     *
     * @param string $username access username
     * @param string $password access password
     * @param array  $options  connection options
     */
    public function __construct($username = null, $password = null, $options = array())
    {
        if ($options instanceof AeArray) {
            $options = $options->getValue();
        }

        if (isset($options['prefix'])) {
            $this->setPrefix($options['prefix']);
        } else {
            $this->setPrefix('ae_');
        }

        if ($username !== null && $password !== null) {
            $this->connect($username, $password, $options);
        }
    }

    /**
     * Establish connection
     *
     * Establish a database connection using credentials and parameters passed.
     * See {@link AeDatabase_Driver::__construct()} for more information.
     *
     * @throws AeDatabaseDriverException #404 if PDO class not found
     * @throws AeDatabaseDriverException #400 if connection data is invalid
     *
     * @param string $username access username
     * @param string $password access password
     * @param array  $attrs    PDO connection options
     * @param string $dsn      PDO connection dsn
     *
     * @return bool true on success, false on failure
     */
    public function connect($username, $password, $attrs = null, $dsn = null)
    {
        if (!class_exists('PDO', false)) {
            throw new AeDatabaseDriverException('PDO class not found', 404);
        }

        if (!is_string($dsn)) {
            throw new AeDatabaseDriverException('Invalid connection dsn: ' . $dsn, 400);
        }

        if (!is_array($attrs)) {
            $attrs = array();
        }

        if (!isset($attrs[PDO::ATTR_ERRMODE])) {
            $attrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_SILENT;
        }

        try {
            if ($attrs === null) {
                $this->setDb(new PDO($dsn, (string) $username, (string) $password));
            } else {
                $this->setDb(new PDO($dsn, (string) $username, (string) $password, $attrs));
            }
        } catch (PDOException $e) {

            if (!is_array($e->errorInfo))
            {
                $message = trim(preg_replace('#^SQLSTATE\[[A-Za-z0-9]+\]#', '', $e->getMessage()));
                $matches = array();

                if (preg_match('#^\[(\d+)\]#', $message, $matches)) {
                    $message = trim(substr($message, strlen($matches[1]) + 2));
                    $code    = (int) $matches[1];
                } else {
                    $code = 500;
                }
            } else {
                $code    = $e->errorInfo[1];
                $message = $e->errorInfo[2];
            }

            throw new AeDatabaseDriverException($message, $code);
        }

        return true;
    }

    /**
     * Terminate connection
     *
     * @return AeInterface_Database self
     */
    public function disconnect()
    {
        $this->setDb(null);

        return $this;
    }

    /**
     * Check if connected
     *
     * Check if a connection is currently established
     *
     * @return bool true if connected, false otherwise
     */
    public function isConnected()
    {
        return (bool) $this->getDb(false);
    }

    /**
     * Set query
     *
     * Set the query to be executed
     *
     * @param string $query  target query
     * @param int    $limit  number of results to show
     * @param int    $offset result offset
     *
     * @return AeInterface_Database self
     */
    public function setQuery($query, $limit = null, $offset = 0)
    {
        if ($limit instanceof AeScalar) {
            $limit = $limit->getValue();
        }

        if ($offset instanceof AeScalar) {
            $offset = $offset->getValue();
        }

        // *** Check if limit and offset were set
        if (is_numeric($limit) && !preg_match('#^\s*INSERT\s+#i', $query)
            && !preg_match('#\s+LIMIT\s+(?:\d+\s*,\s*)?\d+(?:\s+OFFSET\s+\d+)?\s*$#i', $query))
        {
            $query .= "\nLIMIT " . (int) $limit;

            if (is_numeric($offset) && $offset > 0) {
                $query .= ' OFFSET ' . (int) $offset;
            }
        }

        $this->setResult(null);
        $this->_query  = AeDatabase::replacePrefix((string) $query, $this->getPrefix());

        return $this;
    }

    /**
     * Execute query
     *
     * Execute a previously set query. If the optional <var>$data</var>
     * parameter is passed, it will be passed to {@link
     * http://php.net/manual/en/pdostatement.execute.php PDOStatement::execute()}
     * method as data to be bound. It also means, that the PDO::prepare() method
     * is called before the query execution and you can execute several similar
     * queries without the need to specify the query each time.
     * 
     * If there is no data to be bound, {@link
     * http://php.net/manual/en/pdo.query.php PDO::query()} method is used
     * instead of {@link http://php.net/manual/en/pdo.prepare.php
     * PDO::prepare} and {@link http://php.net/manual/en/pdostatement.execute.php
     * PDOStatement::execute()}.
     *
     * @throws AeDatabaseDriverException #412 if query wasn't set by {@link
     *                                   AeDatabase_Driver::setQuery()} method
     * @throws AeDatabaseDriverException instead of PDOException on underlying
     *                                   driver error
     *
     * @see http://php.net/manual/en/pdo.query.php
     * @see http://php.net/manual/en/pdo.prepare.php
     * @see http://php.net/manual/en/pdostatement.execute.php
     *
     * @param array $data data to be bound to the statement (as seen in PDO's
     *                    prepare/execute statements)
     *
     * @event database.driver.execute [query, data] right before query is executed
     *
     * @return bool true if query was successful, false otherwise
     */
    public function execute($data = null)
    {
        if ($this->getQuery() === null) {
            throw new AeDatabaseDriverException('No query set to be executed', 412);
        }

        try
        {
            if ($data !== null)
            {
                if ($data instanceof AeScalar || $data instanceof AeArray) {
                    $data = $data->getValue();
                }

                if (!is_array($data)) {
                    $data = array($data);
                }

                if (!($this->_query instanceof PDOStatement)) {
                    $this->_query = $this->db->prepare($this->getQuery());
                }

                $this->fireEvent('execute', array($this->_query->queryString, $data));

                $return = $this->_query->execute($data);

                if ($return === true) {
                    $return = $this->_query;
                }
            } else {
                $this->fireEvent('execute', array($this->getQuery(), array()));

                $return = $this->db->query($this->getQuery(), PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {

            if (!is_array($e->errorInfo))
            {
                $message = trim(preg_replace('#^SQLSTATE\[[A-Za-z0-9]+\]#', '', $e->getMessage()));
                $matches = array();

                if (preg_match('#^\[(\d+)\]#', $message, $matches)) {
                    $message = trim(substr($message, strlen($matches[1]) + 2));
                    $code    = (int) $matches[1];
                } else {
                    $code = 500;
                }
            } else {
                $message = $e->errorInfo[2];
                $code    = $e->errorInfo[1];
            }

            throw new AeDatabaseDriverException($message, $code);
        }

        if ($return !== false) {
            $this->setResult($return);
            $return = true;
        } else {
            $this->setResult(null);
        }

        return $return;
    }

    /**
     * Execute query and fetch rows
     *
     * This is a shortcut method for the following two calls:
     * <code> $db->setQuery($query);
     * $result = $db->getRows();</code>
     *
     * @param string $query target query
     *
     * @return AeArray an array of results or false if query failed
     */
    public function query($query)
    {
        $this->setQuery((string) $query);
        return $this->getRows();
    }

    /**
     * Return last insert id
     *
     * @return int
     */
    public function getInsertId()
    {
        return $this->getDb()->lastInsertId();
    }

    /**
     * Return affected rows
     *
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->getResult() === null ? false : $this->getResult()->rowCount();
    }

    /**
     * Get result field
     *
     * Returns a single field of the first result row. Returns null, if no fields
     * left to return
     *
     * <b>NOTE</b>: if {@link AeType::$wrapReturn} is set to true, this
     * method will return {@link AeNull}, and the following code will result in
     * an infinite loop:
     * <code> while ($field = $database->getField()) {
     * // some code here</code>
     *
     * You can use the following code as a workaround:
     * <code> while (AeType::of($field = $database->getField()) != 'null') {
     * // some code here</code>
     *
     * @return AeString
     */
    public function getField()
    {
        if ($this->getResult() === null && $this->getQuery(false)) {
            $this->execute();
        }

        if ($this->getResult() === null) {
            return AeType::wrapReturn(null);
        }

        $return = $this->getResult()->fetchColumn();

        if (!$return) {
            $this->setResult(null);
            return AeType::wrapReturn(null);
        }

        return AeType::wrapReturn($return);
    }

    /**
     * Get result object
     *
     * Returns first row of the result as an object. Returns null if nothing left
     * to return. See {@link AeDatabase_Driver::getField()} for additional notes
     * on using this method with the while statement
     *
     * @throws AeDatabaseDriverException #404 if specified class not found
     *
     * @param string $class custom class name
     * @param array  $args  an array of arguments to pass to the class constructor
     *
     * @return object
     */
    public function getObject($class = 'AeNode', $args = null)
    {
        if ($this->getResult() === null && $this->getQuery()) {
            $this->execute();
        }

        if ($this->getResult() === null) {
            return AeType::wrapReturn(null);
        }

        if (!class_exists((string) $class)) {
            throw new AeDatabaseDriverException('Class not found: ' . $class, 404);
        }

        if ($args instanceof AeArray) {
            $args = $args->getValue();
        }

        if (!is_null($args) && count((array) $args) > 0) {
            $return = $this->getResult()->fetchObject((string) $class, (array) $args);
        } else {
            $return = $this->getResult()->fetchObject((string) $class);
        }

        if (!$return) {
            $this->setResult(null);
            return AeType::wrapReturn(null);
        }

        return $return;
    }

    /**
     * Get row count
     *
     * Returns the number of rows in the result set. If the query is set, but
     * not yet executed, this method executes it automatically
     *
     * @return int
     */
    public function getRowCount()
    {
        if ($this->getResult() === null && $this->getQuery()) {
            $this->execute();
        }

        return $this->getAffectedRows();
    }

    /**
     * Get result array
     *
     * Returns first row of the result as an associative array. Returns null if
     * no rows left to return. See {@link AeDatabase_Driver::getField()} for
     * additional notes on using this method with the while statement
     *
     * @return AeArray
     */
    public function getRow()
    {
        if ($this->getResult() === null && $this->getQuery()) {
            $this->execute();
        }

        if ($this->getResult() === null) {
            return AeType::wrapReturn(null);
        }

        $return = $this->getResult()->fetch(PDO::FETCH_ASSOC);

        if (!$return) {
            $this->setResult(null);
            return AeType::wrapReturn(null);
        }

        return AeType::wrapReturn($return);
    }

    /**
     * Get result fields
     *
     * Returns a single result column as an array. Returns null if the result is
     * empty. If <var>$key</var> parameter is set, that field will be used as an
     * index for the results array. This may result in rows being overwritten,
     * so be sure, you are using a field with unique values with this parameter.
     * This also requires at least two field values to be present in the result
     * set. The result's values will be taken from the very first column of the
     * result set:
     * <code> $database->setQuery('SELECT name, id FROM users');
     * $database->execute();
     *
     * $result = $database->getFields('id');
     * print_r($result);</code>
     *
     * The code above will result into something like this:
     * <code> Array
     * (
     *     1 => Mark
     *     2 => John
     * )</code>
     *
     * If {@link AeType::$wrapReturn} is true, this method returns {@link AeNull}
     * instead of null
     *
     * @return AeArray
     */
    public function getFields($key = null)
    {
        if ($this->getResult() === null && $this->getQuery()) {
            $this->execute();
        }

        if ($this->getResult() === null) {
            return AeType::wrapReturn(null);
        }

        $return = array();

        if (!is_null($key))
        {
            $key = (string) $key;

            while (AeType::of($row = $this->getRow()) != 'null')
            {
                if (isset($row->$key)) {
                    $return[$row[$key]] = array_shift($row);
                }
            }
        } else {
            while (AeType::of($row = $this->getField()) != 'null') {
                $return[] = $row;
            }
        }

        $this->setResult(null);

        return AeType::wrapReturn($return);
    }

    /**
     * Get result objects
     *
     * Returns all result rows as an array of objects. Returns null if the result
     * is empty. If <var>$key</var> parameter is set, that field will be used as
     * an index for the results array. This may result in rows being overwritten,
     * so be sure, you are using a field with unique values with this parameter.
     *
     * If {@link AeType::$wrapReturn} is true, this method returns {@link AeNull}
     * instead of null
     *
     * @throws AeDatabaseDriverException #404 if specified class not found
     *
     * @param string $key   index field name
     * @param string $class custom class name
     * @param mixed  $args  an array of arguments to pass to the class constructor
     *
     * @return AeArray
     */
    public function getObjects($key = null, $class = 'AeNode', $args = null)
    {
        if ($this->getResult() === null && $this->getQuery()) {
            $this->execute();
        }

        if ($this->getResult() === null) {
            return AeType::wrapReturn(null);
        }

        if (!class_exists((string) $class)) {
            throw new AeDatabaseDriverException('Class not found: ' . $class, 404);
        }

        if ($args instanceof AeArray) {
            $args = $args->getValue();
        }

        if (!is_null($key)) {
            $key = (string) $key;
        }

        $return = array();

        while (AeType::of($row = $this->getObject((string) $class, (array) $args)) != 'null')
        {
            if (!is_null($key) && isset($row->$key)) {
                $return[$row->$key] = $row;
                continue;
            }

            $return[] = $row;
        }

        $this->setResult(null);

        return AeType::wrapReturn($return);
    }

    /**
     * Get result rows
     *
     * Returns all result rows as a two-dimensional array. Returns null if the
     * result is empty.If <var>$key</var> parameter is set, that field will be
     * used as an index for the results array.
     *
     * If {@link AeType::$wrapReturn} is true, this method returns {@link AeNull}
     * instead of null
     *
     * @param string $key index field name
     *
     * @return AeArray
     */
    public function getRows($key = null)
    {
        if ($this->getResult() === null && $this->getQuery()) {
            $this->execute();
        }

        if ($this->getResult() === null) {
            return AeType::wrapReturn(null);
        }

        if (!is_null($key)) {
            $key = (string) $key;
        }

        $return = array();

        while (AeType::of($row = $this->getRow()) != 'null')
        {
            if (!is_null($key) && isset($row[$key])) {
                $return[(string) $row[$key]] = $row;
                continue;
            }

            $return[] = $row;
        }

        $this->setResult(null);

        return AeType::wrapReturn($return);
    }

    /**
     * Get driver name
     * 
     * @return string
     */
    public function getDriverName()
    {
        if ($this->_driverName === null) {
            $this->_driverName = str_replace('AeDatabase_Driver_', '', $this->getClass());
            $this->_driverName = strtolower($this->_driverName[0]) . substr($this->_driverName, 1);
        }

        return $this->_driverName;
    }

    /**
     * Get query object
     *
     * Returns a query object for the current database driver. See {@link
     * AeDatabase_Query} for the query object documentation
     *
     * @return AeDatabase_Query
     */
    public function queryObject()
    {
        $class = 'AeDatabase_Driver_' . ucfirst($this->driverName) . '_Query';

        if (!class_exists($class)) {
            $class = 'AeDatabase_Query';
        }

        return new $class($this);
    }
}

/**
 * Database driver exception class
 *
 * Database driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseDriverException extends AeDatabaseException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Driver');
        parent::__construct($message, $code);
    }
}
?>