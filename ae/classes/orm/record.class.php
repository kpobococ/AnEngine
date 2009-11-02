<?php

abstract class AeOrm_Record extends AeNode
{
    const STATE_CREATED  = 1;
    const STATE_READY    = 2;
    const STATE_MODIFIED = 3;
    const STATE_DELETED  = 4;

    protected $_recordModel    = null;
    protected $_recordState    = self::STATE_CREATED;
    protected $_modifiedFields = array();

    public function __construct(AeOrm_Model $model)
    {
        $this->_recordModel = $model;
    }

    public function __destruct()
    {
        if ($this->_recordState === self::STATE_MODIFIED && $this->_recordModel->autoSave === true) {
            $this->save();
        }
    }

    public function save()
    {
        $this->_recordModel->saveRecord($this);
    }

    public function delete()
    {
        $this->_recordModel->deleteRecord($this);
    }

    public function set($name, $value)
    {
        $name = (string) $name;

        if ($this->propertyExists($name, 'set'))
        {
            // *** This may be one of the table fields
            if ($this->_recordModel->hasField($name)) {
                $this->_updateField($name, $value);
            }
        }

        parent::set($name, $value);
    }

    protected function _updateField($name, $value)
    {
        $state = $this->_recordState;

        if ($state === self::STATE_CREATED || $state === self::STATE_DELETED) {
            return true;
        }

        $name = ltrim($name, '_');
        $old  = $this->get($name);

        if ($value !== $old && !in_array($name, $this->_modifiedFields))
        {
            $this->_modifiedFields[] = $name;

            if ($this->_recordState === self::STATE_READY) {
                $this->_recordState = self::STATE_MODIFIED;
            }

            return true;
        }

        return false;
    }

    public static function getInstance($id, AeOrm_Model $model, $foreign = null)
    {
        return $model->loadRecord($id, $foreign);
    }
}

?>