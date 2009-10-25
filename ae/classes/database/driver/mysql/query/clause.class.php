<?php
/**
 * @todo write documentation
 */
class AeDatabase_Driver_Mysql_Query_Clause extends AeDatabase_Query_Clause
{
    public function __construct($parent)
    {
        if ($parent instanceof AeDatabase_Driver_Mysql_Query || $parent instanceof AeDatabase_Driver_Mysql_Query_Clause) {
            $this->parent = $parent;
        } else {
            throw new AeDatabaseDriverMysqlQueryClauseException('Invalid parent object type', 400);
        }

        $this->_operators = array_merge($this->_operators, array(
            'REGEXP', 'NOT REGEXP', 'RLIKE', 'SOUNDS LIKE'
        ));

        parent::__construct($parent);
    }

    public function addRegexp($field, $value, $glue = 'AND', $format = null)
    {
        return $this->add($field, $value, 'REGEXP', $glue, $format);
    }

    public function bindRegexp($field, $value, $glue = 'AND', $format = null)
    {
        return $this->bind($field, $value, 'REGEXP', $glue, $format);
    }

    public function addNotRegexp($field, $value, $glue = 'AND', $format = null)
    {
        return $this->add($field, $value, 'NOT REGEXP', $glue, $format);
    }

    public function bindNotRegexp($field, $value, $glue = 'AND', $format = null)
    {
        return $this->bind($field, $value, 'NOT REGEXP', $glue, $format);
    }

    public function addOrRegexp($field, $value, $format = null)
    {
        return $this->add($field, $value, 'REGEXP', 'OR', $format);
    }

    public function bindOrRegexp($field, $value, $format = null)
    {
        return $this->bind($field, $value, 'REGEXP', 'OR', $format);
    }

    public function addOrNotRegexp($field, $value, $format = null)
    {
        return $this->add($field, $value, 'NOT REGEXP', 'OR', $format);
    }

    public function bindOrNotRegexp($field, $value, $format = null)
    {
        return $this->bind($field, $value, 'NOT REGEXP', 'OR', $format);
    }

    public function addSoundsLike($field, $value, $glue = 'AND', $format = null)
    {
        return $this->add($field, $value, 'SOUNDS LIKE', $glue, $format);
    }

    public function bindSoundsLike($field, $value, $glue = 'AND', $format = null)
    {
        return $this->bind($field, $value, 'SOUNDS LIKE', $glue, $format);
    }

    public function addOrSoundsLike($field, $value, $format = null)
    {
        return $this->add($field, $value, 'SOUNDS LIKE', 'OR', $format);
    }

    public function bindOrSoundsLike($field, $value, $format = null)
    {
        return $this->bind($field, $value, 'SOUNDS LIKE', 'OR', $format);
    }
}

/**
 * Mysql query clause exception class
 *
 * Mysql clause-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseDriverMysqlQueryClauseException extends AeDatabaseDriverMysqlQueryException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Clause');
        parent::__construct($message, $code);
    }
}
?>