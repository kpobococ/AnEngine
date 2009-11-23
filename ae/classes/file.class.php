<?php
/**
 * @todo write documentation
 * @todo add exceptions for PHP function failures
 */
class AeFile extends AeObject_File
{
    protected $_handle;
    protected $_handleMode;

    public function setPath($path)
    {
        if (file_exists($path) && !is_file($path)) {
            throw new AeFileException('Invalid value passed: expecting file, ' . AeFile::type($path) . ' given', 400);
        }

        parent::setPath($path);

        if ($this->_path !== null && $this->_path != $path) {
            AeInstance::clear($this->getClass(), array($this->_path));
        }

        return $this;
    }

    public function getMode($octal = true)
    {
        $mode = parent::getMode($octal);

        return $octal === false ? '-' . $mode : $mode;
    }

    protected function _getSize()
    {
        return @filesize($this->path);
    }

    public function getType()
    {
        return 'file';
    }

    public function getParent()
    {
        return AeDirectory::getInstance(dirname($this->path));
    }

    public function create($mode = null)
    {
        if ($this->exists()) {
            throw new AeFileException('Cannot create: file already exists', 412);
        }

        if (!is_writable(dirname($this->path))) {
            throw new AeFileException('Cannot create: parent directory is not writable', 401);
        }

        @mkdir($this->path);

        $this->setPath($this->path);

        if ($mode !== null) {
            $this->setMode($mode);
        }

        return $this;
    }

    public function getHandle($mode = 'r+')
    {
        if (is_resource($this->_handle) && $this->_handleMode != $mode) {
            @fclose($this->_handle);
            $this->_handle     = null;
            $this->_handleMode = null;
        }

        if (!is_resource($this->_handle))
        {
            if (!$this->exists()) {
                throw new AeFileException('Cannot open: file does not exist', 404);
            }

            $read  = false;
            $write = false;

            switch ($mode)
            {
                case 'r':
                case 'r+':
                case 'w':
                case 'w+':
                case 'a':
                case 'a+':
            }

            if (!$this->isReadable()) {
                throw new AeFileException('Cannot open: permission denied', 403);
            }

            $this->_handle = @opendir($this->path);

            if (!$this->_handle) {
                $e = error_get_last();
                throw new AeFileException('Cannot open: ' . $e['message'], 500);
            }
        }

        return $this->_handle;
    }
}

// *** See AeObject_File ae/classes/object/file.class.php
?>