<?php
/**
 * @todo write documentation
 */
class AeDatabase_Driver_Mysql_Query extends AeDatabase_Query
{
    public function __construct(AeDatabase_Driver_Mysql $database)
    {
        parent::__construct($database);
    }
}

/**
 * Mysql query exception class
 *
 * Mysql query-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseDriverMysqlQueryException extends AeDatabaseDriverMysqlException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Query');
        parent::__construct($message, $code);
    }
}
?>