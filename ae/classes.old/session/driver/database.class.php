<?php
/**
 * Database session driver class file
 *
 * See {@link AeSession_Driver_Database} class documentation
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Database session driver class
 *
 * This driver uses database as storage for sessions. The database connection is
 * provided by the user in one of several ways. The detailed options description
 * is available in the {@link AeSession_Driver_Database::__construct()
 * Constructor} method documentation.
 * 
 * You also need set the table name to use. The target table must have several
 * specific fields:
 * - id   CHAR(32) session id field. This should be the primary key;
 * - date DATETIME session last update time. This field is used to clear timed
 *                 out sessions;
 * - data TEXT     session data field. This will store the serialized data of
 *                 the session.
 *
 * The data field can be any of the SQL TEXT types, as long as it has sufficient
 * size to store all the actual session data. You can also use the BLOB type,
 * but there is no real reason to do so.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeSession_Driver_Database extends AeSession_Driver
{
    /**
     * Session storage table name
     * @var string
     */
    protected $_storageTable;

    /**
     * Database connection
     * @var AeInterface_Database
     */
    protected $_connection = null;

    /**
     * Database connection options
     * @var AeArray
     */
    protected $_options;

    /**
     * Constructor
     *
     * Database storage driver accepts the following options:
     * - table      string the table name to be used as the storage table for
     *                     sessions. The target table should meet several simple
     *                     requirements, which are described in the {@link
     *                     AeSession_Driver_Database class documentation};
     * - connection array  connection options array. See below for detailed
     *                     description.
     *
     * To use this session handler, you must provide sufficient data to
     * establish a database connection. There are several sets of data, any of
     * which is sufficient:
     * - name, settings: parameters to be passed to {@link
     *                   AeDatabase::getConnection()} method. See the method
     *                   documentation for more details;
     * - driver, username, password, options: parameters to be passed to {@link
     *                                        AeDatabase::getInstance()} method.
     *                                        See the method documentation for
     *                                        more details;
     * - callback: an instance of the {@link AeCallback} class, specifying which
     *             method should be called to get the active database connection.
     *             The return value of the method must be an implementation of
     *             the {@link AeInterface_Database} class;
     * - connection: an instance of the {@link AeInterface_Database} object.
     *
     * @param AeArray $options
     */
    public function __construct(AeArray $options = null)
    {
        if (isset($options['table'])) {
            $this->_storageTable = (string) $options['table'];
        } else {
            $this->_storageTable = '#__session';
        }

        if (isset($options['connection']))
        {
            if ($options['connection'] instanceof AeInterface_Database) {
                $this->_connection = $options['connection'];
            } else {
                $this->_options = $options['connection'];
            }
        }

        parent::__construct($options);
    }

    /**
     * Get connection
     *
     * Returns a database connection. If the connection is not established, uses
     * options provided to establish one.
     *
     * @throws AeSessionDriverDatabaseException #400 if database options are
     *                                          invalid
     *
     * @return AeInterface_Database
     */
    public function getConnection()
    {
        if ($this->_connection instanceof AeInterface_Database && !$this->_connection->isConnected()) {
            // *** The connection is lost, try to reestablish it
            $this->_connection = null;
        }

        if (!($this->_connection instanceof AeInterface_Database))
        {
            // *** Establish connection first
            if ($this->_options instanceof AeArray)
            {
                // *** An array of settings
                if (isset($this->_options['name'])) {
                    // *** Connection name and settings file path assumed
                    $this->_connection = AeDatabase::getConnection($this->_options['name'], $this->_options['settings']);
                } else if (isset($this->_options['driver'])) {
                    // *** Connection options array assumed
                    $this->_connection = AeDatabase::getInstance(
                        $this->_options['driver'],
                        $this->_options['username'],
                        $this->_options['password'],
                        $this->_options['options']->value
                    );
                } else {
                    // *** Invalid options array
                    throw new AeSessionDriverDatabaseException('Invalid database connection options', 400);
                }
            } else if ($this->_options instanceof AeCallback) {
                // *** A callback
                $this->_connection = $this->_options->call();

                if (!($this->_connection instanceof AeInterface_Database)) {
                    // *** Invalid callback return value
                    throw new AeSessionDriverDatabaseException('Invalid database connection options', 400);
                }
            } else {
                // *** Invalid options value
                throw new AeSessionDriverDatabaseException('Invalid database connection options', 400);
            }
        }

        return $this->_connection;
    }

    /**
     * Garbage collector method
     *
     * This is executed when the session garbage collector is executed and takes
     * the max session lifetime as its only parameter
     *
     * @param int $lifetime max session lifetime in seconds
     *
     * @return bool
     */
    public function clean($lifetime)
    {
        $db      = $this->getConnection();
        $query   = $db->queryObject();
        $expired = AeDate::now('UTC')
                 ->subtract(array('seconds' => $lifetime))
                 ->getValue('Y-m-d H:i:s');

        $query->delete($this->_storageTable)
              ->where()->bind('date', $expired, '<');
        $query->execute();

        return true;
    }

    /**
     * Destroy method
     *
     * This is executed when a session is destroyed with {@link
     * http://php.net/session_destroy session_destroy()} and takes the session
     * id as its only parameter.
     *
     * @param string $id
     *
     * @return bool
     */
    public function destroy($id)
    {
        $db    = $this->getConnection();
        $query = $db->queryObject();

        $query->delete($this->_storageTable)
              ->where()->bind('id', $id);
        $query->execute(1);

        return true;
    }

    /**
     * Read method
     *
     * Always returns string value to make save handler work as expected.
     * Returns empty string if there is no data to read
     *
     * @param string $id
     *
     * @return string
     */
    public function read($id)
    {
        $db    = $this->getConnection();
        $query = $db->queryObject();

        $query->select('data')
              ->from($this->_storageTable)
              ->where()->bind('id', $id);

        $field = $query->execute(1)->getField();

        if (AeType::of($field) == 'null') {
            return '';
        }

        return (string) $field;
    }

    /**
     * Write method
     *
     * Saves session data, takes session id and data to be save as its
     * parameters. The "write" handler is not executed until after the output
     * stream is closed. Thus, output from debugging statements in the "write"
     * handler will never be seen in the browser. If debugging output is
     * necessary, it is suggested that the debug output be written to a file
     * instead.
     *
     * <b>NOTE:</b> Because PHP first destroys all unused objects and then tries
     *              to write session data, you cannot use a database driver,
     *              which disconnects from the database in the destructor.
     *
     * <b>NOTE:</b> Because PHP removes all autoload functions from autoload
     *              stack, this function relies on all the required classes to
     *              be loaded already.
     *
     *
     * @param string $id
     * @param string $data
     *
     * @return bool
     */
    public function write($id, $data)
    {
        require_once 'ae' . SLASH . 'classes' . SLASH . 'date.class.php';
        require_once 'ae' . SLASH . 'classes' . SLASH . 'date' . SLASH . 'timezone.class.php';

        $data = new AeNode(array(
            'id'   => $id,
            'data' => $data,
            'date' => AeDate::now('UTC')
        ));

        if ($this->fireEvent('write', $data))
        {
            $db    = $this->getConnection();
            $query = $db->queryObject();
            $data  = $data->getProperties();

            $data['date'] = $data['date']->getValue('Y-m-d H:i:s');

            // *** Bind all data
            foreach ($data as $field => $value) {
                $data[$field] = $query->bind($field, $value);
            }

            $query->replace($this->_storageTable)
                  ->values($data)
                  ->execute();
            return true;
        }

        return false;
    }
}
/**
 * Database session driver exception class
 *
 * Database session driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSessionDriverDatabaseException extends AeSessionDriverException
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