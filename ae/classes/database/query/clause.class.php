<?php
/**
 * @todo write documentation
 */
class AeDatabase_Query_Clause extends AeObject
{
    protected $_bits = array();
    protected $_query;
    protected $_parent;
    protected $_format = '%s %s %s';

    protected $_operators = array(
        '=', '>', '>=', '<', '<=', '!=', '<>',
        'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'
    );
    protected $_glues = array('AND', 'OR', 'XOR');

    public function __construct($parent)
    {
        if ($parent instanceof AeDatabase_Query || $parent instanceof AeDatabase_Query_Clause) {
            $this->parent = $parent;
        } else {
            throw new AeDatabaseQueryClauseException('Invalid parent object type', 400);
        }

        if ($parent instanceof AeDatabase_Query) {
            $this->query = $parent;
        } else {
            $this->query = $this->parent->query;
        }
    }

    public function add($field, $value, $operator = '=', $glue = 'AND', $format = null)
    {
        $operator = strtoupper(trim($operator));
        $glue     = strtoupper(trim($glue));

        if (!in_array($operator, $this->_operators)) {
            throw new AeDatabaseQueryClauseException('Invalid operator value', 400);
        }

        if (!in_array($glue, $this->_glues)) {
            throw new AeDatabaseQueryClauseException('Invalid glue value', 400);
        }

        $field = $this->query->field($field, true);
        $value = $value instanceof AeType ? $value->getValue() : $value;

        if ($operator == 'IN' || $operator == 'NOT IN')
        {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $value = '(' . $value . ')';
        } else if ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
            if (!is_array($value)) {
                throw new AeDatabaseQueryClauseException('Invalid value', 400);
            }

            if ($format === null) {
                $format = '%s %s %s AND %s';
            }
        }

        $format = $format !== null ? $format : $this->_format;

        if ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
            $bit = sprintf($format, $field, $operator, $value[0], $value[1]);
        } else {
            $bit = sprintf($format, $field, $operator, $value);
        }

        $this->_bits[] = array($bit, $glue);

        return $this;
    }

    public function bind($field, $value, $operator = '=', $glue = 'AND', $format = null)
    {
        $operator = strtoupper(trim($operator));
        $value    = $value instanceof AeType ? $value->getValue() : $value;

        if (($operator == 'IN' || $operator == 'NOT IN') && is_array($value))
        {
            foreach ($value as $f => $v) {
                $value[$f] = $this->query->bind($field, $v);
            }
        } else if ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
            if (!is_array($value)) {
                throw new AeDatabaseQueryClauseException('Invalid value', 400);
            }

            $value[0] = $this->query->bind($field, $value[0]);
            $value[1] = $this->query->bind($field, $value[1]);
        } else {
            $value = $this->query->bind($field, $value);
        }

        return $this->add($field, $value, $operator, $glue, $format);
    }

    public function addOr($field, $value, $operator = '=', $format = null)
    {
        return $this->add($field, $value, $operator, 'OR', $format);
    }

    public function bindOr($field, $value, $operator = '=', $format = null)
    {
        return $this->bind($field, $value, $operator, 'OR', $format);
    }

    public function addLike($field, $value, $glue = 'AND', $format = null)
    {
        return $this->add($field, $value, 'LIKE', $glue, $format);
    }

    public function bindLike($field, $value, $glue = 'AND', $format = null)
    {
        return $this->bind($field, $value, 'LIKE', $glue, $format);
    }

    public function addNotLike($field, $value, $glue = 'AND', $format = null)
    {
        return $this->add($field, $value, 'NOT LIKE', $glue, $format);
    }

    public function bindNotLike($field, $value, $glue = 'AND', $format = null)
    {
        return $this->bind($field, $value, 'NOT LIKE', $glue, $format);
    }

    public function addOrLike($field, $value, $format = null)
    {
        return $this->add($field, $value, 'LIKE', 'OR', $format);
    }

    public function bindOrLike($field, $value, $format = null)
    {
        return $this->bind($field, $value, 'LIKE', 'OR', $format);
    }

    public function addOrNotLike($field, $value, $format = null)
    {
        return $this->add($field, $value, 'NOT LIKE', 'OR', $format);
    }

    public function bindOrNotLike($field, $value, $format = null)
    {
        return $this->bind($field, $value, 'NOT LIKE', 'OR', $format);
    }

    public function addBetween($field, $value1, $value2, $glue = 'AND', $format = null)
    {
        return $this->add($field, array($value1, $value2), 'BETWEEN', $glue, $format);
    }

    public function bindBetween($field, $value1, $value2, $glue = 'AND', $format = null)
    {
        return $this->bind($field, array($value1, $value2), 'BETWEEN', $glue, $format);
    }

    public function addNotBetween($field, $value1, $value2, $glue = 'AND', $format = null)
    {
        return $this->add($field, array($value1, $value2), 'NOT BETWEEN', $glue, $format);
    }

    public function bindNotBetween($field, $value1, $value2, $glue = 'AND', $format = null)
    {
        return $this->bind($field, array($value1, $value2), 'NOT BETWEEN', $glue, $format);
    }

    public function addOrBetween($field, $value1, $value2, $format = null)
    {
        return $this->addBetween($field, $value1, $value2, 'OR', $format);
    }

    public function bindOrBetween($field, $value1, $value2, $format = null)
    {
        return $this->bindBetween($field, $value1, $value2, 'OR', $format);
    }

    public function addOrNotBetween($field, $value1, $value2, $format = null)
    {
        return $this->addNotBetween($field, $value1, $value2, 'OR', $format);
    }

    public function bindOrNotBetween($field, $value1, $value2, $format = null)
    {
        return $this->bindNotBetween($field, $value1, $value2, 'OR', $format);
    }

    public function addIn($field, $values, $glue = 'AND', $format = null)
    {
        return $this->add($field, $values, 'IN', $glue, $format);
    }

    public function bindIn($field, $values, $glue = 'AND', $format = null)
    {
        return $this->bind($field, $values, 'IN', $glue, $format);
    }

    public function addNotIn($field, $values, $glue = 'AND', $format = null)
    {
        return $this->add($field, $values, 'NOT IN', $glue, $format);
    }

    public function bindNotIn($field, $values, $glue = 'AND', $format = null)
    {
        return $this->bind($field, $values, 'NOT IN', $glue, $format);
    }

    public function addOrIn($field, $values, $format = null)
    {
        return $this->addIn($field, $values, 'OR', $format);
    }

    public function bindOrIn($field, $values, $format = null)
    {
        return $this->bindIn($field, $values, 'OR', $format);
    }

    public function addOrNotIn($field, $values, $format = null)
    {
        return $this->addNotIn($field, $values, 'OR', $format);
    }

    public function bindOrNotIn($field, $values, $format = null)
    {
        return $this->bindNotIn($field, $values, 'OR', $format);
    }

    public function clause()
    {
        $args   = func_get_args();
        $clause = $this->_query->clause($this, $args);

        $this->_bits[] = $clause;

        return $clause;
    }

    public function toString($pad = 5)
    {
        $return = '';

        if (count($this->_bits) > 0)
        {
            if (count($this->_bits) == 1 && $this->_bits[0] instanceof AeDatabase_Query_Clause) {
                // *** Simplification
                return (string) $this->_bits[0];
            }

            foreach ($this->_bits as $bit)
            {
                if ($bit instanceof AeDatabase_Query_Clause)
                {
                    $glue = $bit->getGlue();

                    if (count($bit->bits) > 1) {
                        $bit = '(' . $bit->toString($pad + 5) . ')';
                    } else {
                        // *** Simplification
                        $bit = $bit->toString($pad);
                    }
                } else {
                    $glue = $bit[1];
                    $bit  = $bit[0];
                }

                if ($bit == '') {
                    continue;
                }

                if ($return != '') {
                    $return .= str_pad($glue, $pad, ' ', STR_PAD_LEFT) . ' ';
                }

                $return .= (string) $bit . "\n";
            }
        }

        return rtrim($return);
    }

    public function getGlue()
    {
        if (count($this->_bits) == 0) {
            return '';
        }

        if ($this->_bits[0] instanceof AeDatabase_Query_Clause) {
            return $this->_bits[0]->getGlue();
        }

        return $this->_bits[0][1];
    }

    public function cloneObject(AeDatabase_Query $query, AeDatabase_Query_Clause $parent = null)
    {
        $clone         = clone $this;
        $clone->_query = $query;

        if ($parent !== null) {
            $clone->_parent = $parent;
        } else {
            $clone->_parent = $query;
        }

        // *** Clone subclosures
        $bits = array();

        foreach ($this->_bits as $bit)
        {
            if ($bit instanceof AeDatabase_Query_Clause) {
                $bits[] = $bit->cloneObject($query, $this);
            } else {
                $bits[] = $bit;
            }
        }

        $clone->_bits = $bits;

        return $clone;
    }
}

/**
 * Database query clause exception class
 *
 * Database clause-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDatabaseQueryClauseException extends AeDatabaseQueryException
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