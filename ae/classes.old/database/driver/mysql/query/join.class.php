<?php
/**
 * @todo write documentation
 */
class AeDatabase_Driver_Mysql_Query_Join extends AeDatabase_Query_Join
{
    public function __construct(AeDatabase_Driver_Mysql_Query $query, $table, $type = null)
    {
        $this->_types = array_merge($this->_types, array(
            'INNER', 'CROSS', 'STRAIGHT_JOIN', 'LEFT OUTER', 'RIGHT OUTER',
            'NATURAL LEFT', 'NATURAL RIGHT', 'NATURAL LEFT OUTER', 'NATURAL RIGHT OUTER'
        ));

        parent::__construct($query, $table, $type);
    }
}

/**
 * Mysql query join exception class
 *
 * Mysql query join-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseDriverMysqlQueryJoinException extends AeDatabaseDriverMysqlQueryException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Join');
        parent::__construct($message, $code);
    }
}
?>