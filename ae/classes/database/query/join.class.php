<?php
/**
 * @todo write documentation
 */
class AeDatabase_Query_Join extends AeObject
{
    protected $_query;
    protected $_table;
    protected $_type;
    protected $_on;
    protected $_types = array('LEFT', 'RIGHT', 'FULL');

    public function __construct(AeDatabase_Query $query, $table, $type = null)
    {
        if ($type === null) {
            $type = '';
        }

        $type = strtoupper($type);

        if ($type != '' && !in_array($type, $this->_types)) {
            throw new AeDatabaseQueryJoinException('Invalid join type', 400);
        }

        $this->query = $query;
        $this->type  = trim($type . ' JOIN');
        $this->table = $table;
        $this->on    = $query->clause();
    }

    public function add($field, $value, $operator = '=', $glue = 'AND', $format = null)
    {
        return $this->on->add($field, $value, $operator, $glue, $format);
    }

    public function bind($field, $value, $operator = '=', $glue = 'AND', $format = null)
    {
        return $this->on->bind($field, $value, $operator, $glue, $format);
    }

    public function toString()
    {
        $return = $this->type . ' ' . $this->table . ' ON ';
        $bits   = $this->on->toString(strlen($return) + 4);

        if (count($this->on->bits) > 1) {
            return $return . '(' . $bits . ')';
        } else {
            return $return . $bits;
        }
    }

    public function cloneObject(AeDatabase_Query $query)
    {
        $clone         = clone $this;
        $clone->_query = $query;

        // *** Clone the ON closure
        $clone->_on    = $this->_on->cloneObject($query);

        return $clone;
    }
}

/**
 * Database query join exception class
 *
 * Database query join-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseQueryJoinException extends AeDatabaseQueryException
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