<?php
/**
 * @todo write documentation
 */
class AeDatabase_Query extends AeObject
{
    const TYPE_SELECT = 1;
    const TYPE_UPDATE = 2;
    const TYPE_INSERT = 3;
    const TYPE_REPLACE = 4;
    const TYPE_DELETE = 5;

    protected $_database;
    protected $_type = null;
    protected $_select = null;
    protected $_from = null;
    protected $_fields = null;
    protected $_values = null;
    protected $_set = null;
    protected $_join = null;
    protected $_where = null;
    protected $_group = null;
    protected $_having = null;
    protected $_order = null;
    protected $_data = null;

    public function __construct(AeInterface_Database $database)
    {
        $this->database = $database;
    }

    public function select()
    {
        $this->_type = self::TYPE_SELECT;
        $args = func_get_args();

        if (!is_array($this->_select) && count($args) > 0) {
            $this->_select = array();
        }

        foreach ($args as $arg)
        {
            if ($arg instanceof AeScalar || $arg instanceof AeArray) {
                $arg = $arg->getValue();
            }

            $arg = (array) $arg;

            foreach ($arg as $alias => $field)
            {
                $field = $this->field($field, true);

                if (!is_numeric($alias)) {
                    $this->_select[] = $field . ' AS ' . $this->alias($alias);
                } else {
                    $this->_select[] = $field;
                }
            }
        }

        return $this;
    }

    public function update($table, $alias = null)
    {
        $this->_type = self::TYPE_UPDATE;

        return $this->from($table, $alias);
    }

    public function insert($table)
    {
        $this->_type = self::TYPE_INSERT;

        return $this->from((string) $table);
    }

    public function replace($table)
    {
        $this->_type = self::TYPE_REPLACE;

        return $this->from((string) $table);
    }

    public function delete($table)
    {
        $this->_type = self::TYPE_DELETE;

        return $this->from((string) $table);
    }

    public function set($field, $value = null)
    {
        if ($field instanceof AeScalar || $field instanceof AeArray) {
            $field = $field->getValue();
        }

        // *** Backward compatibility
        if (is_string($field) && $this->propertyExists($field, 'set')) {
            return parent::set($field, $value);
        }

        $field = (array) $field;

        if (!is_array($this->_set) && count($field) > 0) {
            $this->_set = array();
        }

        foreach ($field as $f => $v) {
            $f = $this->field($f);
            // *** $v should be bound explicitly
            $this->_set[] = $f . ' = ' . $v;
        }

        return $this;
    }

    public function fields($fields)
    {
        if (!is_array($this->_fields)) {
            $this->_fields = array();
        }

        if ($fields instanceof AeArray) {
            $fields = $fields->getValue();
        }

        foreach ($fields as $field) {
            $this->_fields[] = $this->field($field);
        }

        return $this;
    }

    public function values($value)
    {
        $args = func_get_args();

        if (!is_array($this->_values)) {
            $this->_values = array();
        }

        foreach ($args as $value)
        {
            if ($value instanceof AeArray) {
                $value = $value->getValue();
            }

            if (!is_array($this->_fields)) {
                $this->fields(array_keys($value));
            }

            $val = array();

            foreach ($value as $f => $v) {
                $val[] = $v;
            }

            $this->_values[] = '(' . implode(', ', $val) . ')';
        }

        return $this;
    }

    public function from($table, $alias = null)
    {
        if ($table instanceof AeType) {
            $table = $table->getValue();
        }

        if ($alias instanceof AeType) {
            $alias = $alias->getValue();
        }

        if ($alias === null) {
            $alias = array();
        }

        $table = (array) $table;
        $alias = (array) $alias;

        if (!is_array($this->_from) && count($table) > 0) {
            $this->_from = array();
        }

        foreach ($table as $i => $name)
        {
            if (isset($alias[$i])) {
                $this->_from[] = $this->table($name) . ' AS ' . $this->alias($alias[$i]);
            } else {
                $this->_from[] = $this->table($name);
            }
        }

        return $this;
    }

    public function join($table, $field, $value, $type = null, $operator = '=')
    {
        static $class = null;

        if ($class === null)
        {
            $class = 'AeDatabase_Driver_' . ucfirst($this->database->driverName) . '_Query_Join';

            if (!class_exists($class)) {
                $class = 'AeDatabase_Query_Join';
            }
        }

        if ($table instanceof AeScalar || $table instanceof AeArray) {
            $table = $table->getValue();
        }

        if (is_array($table) && isset($table[1])) {
            $alias = (string) $table[1];
            $table = (string) $table[0];
        } else {
            $table = (string) $table;
        }

        if (isset($alias)) {
            $table = $this->table($table) . ' AS ' . $this->alias($alias);
        } else {
            $table = $this->table($table);
        }

        $join = new $class($this, $table, $type);

        $join->call('add', array($field, $value, $operator));

        if (!is_array($this->_join)) {
            $this->_join = array();
        }

        $this->_join[] = $join;

        return $join;
    }

    public function getWhere()
    {
        if ($this->_where === null) {
            $this->_where = $this->clause();
        }

        return $this->_where;
    }

    /**
     * @return AeDatabase_Query_Clause
     */
    public function where()
    {
        $args = func_get_args();

        if (count($args) > 0) {
            $this->where->call('add', $args);
        }

        return $this->where;
    }

    public function clause(AeDatabase_Query_Clause $parent = null, $args = array())
    {
        static $class = null;

        if ($class === null)
        {
            $class = 'AeDatabase_Driver_' . ucfirst($this->database->driverName) . '_Query_Clause';

            if (!class_exists($class)) {
                $class = 'AeDatabase_Query_Clause';
            }
        }

        if ($parent === null) {
            $clause = new $class($this);
        } else {
            $clause = new $class($parent);
        }

        if (count($args) > 0) {
            $clause->call('add', $args);
        }

        return $clause;
    }

    public function group($field, $sort = 'ASC')
    {
        $field = $this->field($field);
        $sort  = strtoupper($sort);

        if (!in_array($sort, array('ASC', 'DESC'))) {
            throw new AeDatabaseQueryException('Invalid group sort value', 400);
        }

        $this->_group = array($field, $sort);

        return $this;
    }

    public function getHaving()
    {
        if ($this->_having === null) {
            $this->_having = $this->clause();
        }

        return $this->_having;
    }

    public function having()
    {
        $args = func_get_args();

        if (count($args) > 0) {
            $this->having->call('add', $args);
        }

        return $this->having;
    }

    public function order($field, $sort = 'ASC')
    {
        $field = $this->field($field, true);
        $sort  = strtoupper($sort);

        if (!in_array($sort, array('ASC', 'DESC'))) {
            throw new AeDatabaseQueryException('Invalid order sort value', 400);
        }

        if ($this->_order === null) {
            $this->_order = array();
        }

        $this->_order[] = $field . ' ' . $sort;

        return $this;
    }

    public function execute($limit = null, $start = 0)
    {
        $this->_database->setQuery($this, $limit, $start);

        if (!$this->_database->execute($this->_data)) {
            throw new AeDatabaseQueryException('Query execution failed', 500);
        }

        return $this->_database;
    }

    public function toString()
    {
        switch ($this->_type)
        {
            case self::TYPE_SELECT: {
                return $this->_makeSelect();
            } break;

            case self::TYPE_UPDATE: {
                return $this->_makeUpdate();
            } break;

            case self::TYPE_INSERT: {
                return $this->_makeInsert();
            } break;

            case self::TYPE_REPLACE: {
                return $this->_makeReplace();
            } break;

            case self::TYPE_DELETE: {
                return $this->_makeDelete();
            } break;

            default: {
                throw new AeDatabaseQueryException('Invalid query type', 400);
            } break;
        }
    }

    protected function _makeSelect()
    {
        // *** SELECT
        $query  = "SELECT ";
        $query .= (count($this->_select) > 0 ? implode(",\n       ", $this->_select) : '*') . "\n";

        // *** FROM
        $query .= "FROM ";
        if ($this->_join !== null) {
            // *** Add parentheses to comply with MySQL 5.0.12
            $query .= "(" . implode(",\n     ", $this->_from) . ")\n";
        } else {
            $query .= implode(",\n     ", $this->_from) . "\n";
        }

        // *** JOIN
        if ($this->_join !== null) {
            $query .= implode("\n", $this->_join) . "\n";
        }

        // *** WHERE
        if ($this->_where instanceof AeDatabase_Query_Clause) {
            $query .= "WHERE ";
            $query .= $this->_where->toString() . "\n";
        }

        // *** GROUP BY
        if ($this->_group !== null) {
            $query .= "GROUP BY ";
            $query .= implode(' ', $this->_group) . "\n";
        }

        // *** HAVING
        if ($this->_having instanceof AeDatabase_Query_Clause) {
            $query .= "HAVING ";
            $query .= $this->_having->toString(6) . "\n";
        }

        // *** ORDER BY
        if ($this->_order !== null) {
            $query .= "ORDER BY ";
            $query .= implode(', ', $this->_order) . "\n";
        }

        return rtrim($query);
    }

    protected function _makeUpdate()
    {
        // *** UPDATE
        $query  = "UPDATE ";
        $query .= implode(",\n       ", $this->_from) . "\n";

        // *** SET
        $query .= "SET ";
        $query .= implode(",\n    ", $this->_set) . "\n";

        // *** WHERE
        if ($this->_where instanceof AeDatabase_Query_Clause) {
            $query .= "WHERE ";
            $query .= $this->_where->toString() . "\n";
        }

        // *** ORDER BY
        if ($this->_order !== null) {
            $query .= "ORDER BY ";
            $query .= implode(', ', $this->_order) . "\n";
        }

        return rtrim($query);
    }

    protected function _makeInsert()
    {
        // *** INSERT
        $query  = "INSERT INTO ";
        $query .= $this->_from[0] . "\n";

        if (is_array($this->_set)) {
            // *** SET
            $query .= "SET ";
            $query .= implode(",\n    ", $this->_set) . "\n";
        } else {
            // *** FIELDS
            $query .= '(' . implode(', ', $this->_fields) . ')' . "\n";

            // *** VALUES
            $query .= "VALUES\n";
            $query .= implode(",\n", $this->_values);
        }

        return rtrim($query);
    }

    protected function _makeReplace()
    {
        // *** REPLACE
        $query  = "REPLACE INTO ";
        $query .= $this->_from[0] . "\n";

        if (is_array($this->_set)) {
            // *** SET
            $query .= "SET ";
            $query .= implode(",\n    ", $this->_set) . "\n";
        } else {
            // *** FIELDS
            $query .= '(' . implode(', ', $this->_fields) . ')' . "\n";

            // *** VALUES
            $query .= "VALUES\n";
            $query .= implode(",\n", $this->_values);
        }

        return rtrim($query);
    }

    protected function _makeDelete()
    {
        // *** DELETE
        $query  = "DELETE FROM ";
        $query .= $this->_from[0] . "\n";

        // *** WHERE
        if ($this->_where instanceof AeDatabase_Query_Clause) {
            $query .= "WHERE ";
            $query .= $this->_where->toString() . "\n";
        }

        // *** ORDER BY
        if ($this->_order !== null) {
            $query .= "ORDER BY ";
            $query .= implode(', ', $this->_order) . "\n";
        }

        return rtrim($query);
    }

    protected function _cleanQuotes($value, $wrap = true, $symbol = '`')
    {
        $first  = $value[0];
        $last   = $value[strlen($value) - 1];
        $symbol = (array) $symbol;

        if (in_array($first, $symbol) || in_array($last, $symbol))
        {
            if ($first == $last) {
                $value = substr($value, 1, -1);
            } else if (in_array($first, $symbol)) {
                $value = substr($value, 1);
            } else {
                $value = substr($value, 0, -1);
            }
        }

        if ($wrap) {
            $value = $symbol[0] . $value . $symbol[0];
        }

        return $value;
    }

    public function field($field, $supportFunctions = false)
    {
        $field = trim($field);

        if ($supportFunctions === true && strpos($field, '(') && preg_match('#^[-_a-z0-9]+\(.*\)$#i', $field)) {
            // *** Looks like a function
            return $field;
        }

        // *** Check for table alias
        if (strpos($field, '.') !== false) {
            list ($alias, $field) = explode('.', $field);
            $alias  = $this->alias($alias);
            $alias .= '.';
        } else {
            $alias = '';
        }

        // *** Check for field escape
        $field = $this->_cleanQuotes($field, false);

        if ($field != '*') {
            $field = '`' . $field . '`';
        }

        return $alias . $field;
    }

    public function alias($alias)
    {
        return $this->_cleanQuotes($alias);
    }

    public function table($table)
    {
        return $this->_cleanQuotes($table);
    }

    public function bind($field, $value)
    {
        static $indexes = array();

        $field = $this->field($field);
        $dot   = strpos($field, '.');

        if ($dot !== false) {
            // *** Table alias present
            $name = substr($field, $dot + 1);
        } else {
            $name = $field;
        }

        $name = substr($name, 1, -1);

        if (isset($this->_data[$name]))
        {
            if (!isset($indexes[$name])) {
                $indexes[$name] = 0;
            }

            $name .= ++$indexes[$name];
        }

        $this->_data[$name] = $value;

        return ':' . $name;
    }

    public function __clone()
    {
        if ($this->_where !== null) {
            // *** Use custom clone method to reassign objects recursively
            $this->_where = $this->_where->cloneObject($this);
        }

        if (is_array($this->_join))
        {
            // *** Clone joins
            $joins = array();

            foreach ($this->_join as $join) {
                // *** Use custom clone method to reassign objects recursively
                $joins[] = $join->cloneObject($this);
            }

            $this->_join = $joins;
        }
    }
}

/**
 * Database query exception class
 *
 * Database query-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseQueryException extends AeDatabaseException
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