<?php

class AeDirectory_Iterator extends AeObject implements Iterator
{
    protected $_current = null;
    protected $_key = null;
    protected $_directory;

    public static function getInstance(AeDirectory $directory)
    {
        return AeInstance::get('AeDirectory_Iterator', array($directory), true, false);
    }

    /**
     * Constructor
     *
     * @param AeDirectory $directory
     */
    public function __construct(AeDirectory $directory)
    {
        $this->_directory = $directory;
        $this->rewind();
    }

    public function rewind()
    {
        $dh = $this->directory->getHandle();

        @rewinddir($dh);

        do {
            $current = @readdir($dh);
        } while ($current == '.' || $current == '..');

        if ($current !== false) {
            $this->_current = $current;
            $this->_key     = 0;
        } else {
            $this->_current = null;
            $this->_key     = null;
        }

        return true;
    }

    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        return AeObject_File::wrap($this->directory->path . SLASH . $this->_current);
    }

    public function key()
    {
        return $this->_key;
    }

    public function next()
    {
        $dh = $this->directory->getHandle();

        do {
            $current = @readdir($dh);
        } while ($current == '.' || $current == '..');

        if ($current !== false) {
            $this->_current = $current;
            $this->_key++;
        } else {
            $this->_current = null;
            $this->_key     = null;
        }

        return $this->current();
    }

    public function valid()
    {
        return ($this->key() !== null);
    }
}
?>