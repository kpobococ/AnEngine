<?php

class AeDirectory_Iterator extends AeObject implements Iterator
{
    protected $_current = null;
    protected $_key = null;
    protected $_directory;
    protected $_handle;

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

    public function getHandle()
    {
        if (!is_resource($this->_handle))
        {
            if (!$this->directory->exists()) {
                throw new AeDirectoryIteratorException('Cannot open: directory does not exist', 404);
            }

            if (!$this->directory->isReadable()) {
                throw new AeDirectoryIteratorException('Cannot open: permission denied', 403);
            }

            $this->_handle = @opendir($this->directory->path);

            if (!$this->_handle) {
                $e = error_get_last();
                throw new AeDirectoryIteratorException('Cannot open: ' . $e['message'], 500);
            }
        }

        return $this->_handle;
    }

    public function rewind()
    {
        $dh = $this->getHandle();

        @rewinddir($dh);

        $current = @readdir($dh);

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
        $dh = $this->getHandle();

        $current = @readdir($dh);

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