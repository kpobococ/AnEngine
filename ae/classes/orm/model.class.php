<?php

abstract class AeOrm_Model extends AeObject
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_STRING = 'string';
    const TYPE_ARRAY = 'array';
    const TYPE_TEXT = 'text';
    const TYPE_BINARY = 'binary';
    const TYPE_TIME = 'time';
    const TYPE_DATE = 'date';

    protected $_tableName;
    protected $_aliasName;
    protected $_recordClass;

    protected $_fields = array(
        'id' => array( # object's field alias
            'name'     => 'id', # actual field name in the table
            'type'     => 'integer',
            'length'   => 10,
            'unsigned' => true,
            'null'     => false,
            'primary'  => true,
            'auto'     => true,
            'default'  => 0
        )
    );

    // TODO: add multi-field primary key support
    protected $_primaryKey  = 'id'; # object's pk alias
    protected $_foreignKeys = array();

    protected $_recordCache = array();
    protected $_autoSave    = false;
    protected $_autoForeign = true;

    /**
     *
     * @var AeInterface_Database
     */
    protected $_database = null;

    public function hasField($name)
    {
        return in_array($name, array_keys($this->_fields));
    }

    public function loadRecord($id, $foreign = null)
    {
        $type = AeType::of($foreign);

        if ($type == 'boolean') {
            $foreign = $foreign instanceof AeType ? $foreign->getValue() : $foreign;
        } else if ($type != 'null') {
            throw new AeOrmModelException('Invalid foreign value type: expecting boolean or null, ' . $type . ' given', 400);
        } else {
            $foreign = $this->_autoForeign;
        }

        $class = $this->_recordClass;

        if (!class_exists($class)) {
            throw new AeOrmModelException('Record class not found', 404);
        }

        $this->loadTableDefinition();
        // TODO: add foreign records selection
        #$this->loadTableRelations();

        $query = $this->_database->queryObject();

        $query->select();
        $query->from($this->_tableName, $this->_aliasName);

        // TODO: move this into a separate protected method
        $name = $this->_fields[$this->_primaryKey]['name'];

        $query->where($name, $query->bind($name, $id));

        $row = $query->execute(1)->getRow();

        if (AeType::of($row) == null) {
            throw new AeOrmModelException('Record not found', 404);
        }

        $record = new $class($this);

        // TODO: add foreign records assignment
        foreach ($this->_fields as $alias => $params) {
            $record->set($alias, $row[$params['name']]);
        }

        $record->state = AeOrm_Record::STATE_READY;

        return $record;
    }

    public function saveRecord(AeOrm_Record $record)
    {
        if ($record->state == AeOrm_Record::STATE_READY) {
            return $record;
        }

        if ($record->state == AeOrm_Record::STATE_MODIFIED) {
            return $this->updateRecord($record);
        }

        return $this->insertRecord($record);
    }

    public function insertRecord(AeOrm_Record $record)
    {
        if ($record->state == AeOrm_Record::STATE_READY) {
            return $record;
        }

        if ($record->state == AeOrm_Record::STATE_MODIFIED) {
            throw new AeOrmModelException('Modified record cannot be inserted, use updateRecord instead', 405);
        }

        $this->loadTableDefinition();
        // TODO: add foreign records validation
        #$this->loadTableRelations();

        $query  = $this->_database->queryObject();
        $values = array();

        foreach ($this->_fields as $alias => $params)
        {
            if ($params['primary'] == true && $params['auto'] == true) {
                continue;
            }

            $name          = $params['name'];
            $value         = $record->get($alias, $params['default']);
            $values[$name] = $query->bind($name, $value);
        }

        $query->insert($this->_tableName);
        $query->values($values);

        $id = $query->execute()->insertId;

        $record->set($this->_primaryKey, $id);
        $record->state = AeOrm_Record::STATE_READY;

        return $record;
    }

    public function updateRecord(AeOrm_Record $record)
    {
        if ($record->state == AeOrm_Record::STATE_READY) {
            return $record;
        }

        if ($record->state == AeOrm_Record::STATE_CREATED) {
            throw new AeOrmModelException('Created record cannot be updated, use insertRecord instead', 405);
        }

        if ($record->state == AeOrm_Record::STATE_DELETED) {
            throw new AeOrmModelException('Deleted record cannot be updated, use insertRecord instead', 405);
        }

        $this->loadTableDefinition();
        // TODO: add foreign records validation
        #$this->loadTableRelations();

        $query  = $this->_database->queryObject();
        $values = array();

        foreach ($this->_fields as $alias => $params)
        {
            if (!in_array($alias, $record->modifiedFields)) {
                continue;
            }

            $name          = $params['name'];
            $value         = $record->get($alias);
            $values[$name] = $query->bind($name, $value);
        }

        $query->update($this->_tableName);
        $query->set($values);

        // TODO: move this into a separate protected method
        $name = $this->_fields[$this->_primaryKey]['name'];

        $query->where($name, $query->bind($name, $record->get($this->_primaryKey)));
        $query->execute(1);

        $record->state = AeOrm_Record::STATE_READY;

        return $record;
    }

    public function deleteRecord(AeOrm_Record $record)
    {
        if ($record->state == AeOrm_Record::STATE_DELETED) {
            return $record;
        }

        if ($record->state == AeOrm_Record::STATE_CREATED) {
            throw new AeOrmModelException('Created record cannot be deleted', 405);
        }

        $this->loadTableDefinition();
        // TODO: add foreign records onDelete action
        #$this->loadTableRelations();

        $query = $this->_database->queryObject();

        $query->delete($this->_tableName);

        // TODO: move this into a separate protected method
        $name = $this->_fields[$this->_primaryKey]['name'];

        $query->where($name, $query->bind($name, $record->get($this->_primaryKey)));
        $query->execute(1);

        $record->state = AeOrm_Record::STATE_DELETED;

        return $record;
    }

    abstract public function loadTableDefinition();
    abstract public function loadTableRelations();

    protected function __construct(AeInterface_Database $database)
    {
        $this->_database = $database;
    }

    public static function getInstance($class, AeInterface_Database $database)
    {
        if (!$database->isConnected()) {
            throw new AeOrmModelException('Database connection must be established first', 401);
        }

        if (!is_subclass_of($class, 'AeOrm_Model')) {
            throw new AeOrmModelException('Class must be a subclass of AeOrm_Model', 405);
        }

        static $models = array();

        $key = md5($database->getClass() . $class);

        if (!isset($models[$key])) {
            $models[$key] = new $class($database);
        }

        return $models[$key];
    }
}

?>