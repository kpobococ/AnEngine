<?php
/**
 * @todo write documentation
 */
class AeDirectory extends AeObject_File implements Countable, IteratorAggregate
{
    protected $_handle;

    public function setPath($path)
    {
        if (file_exists($path) && !is_dir($path)) {
            throw new AeDirectoryException('Invalid value passed: expecting directory, ' . AeFile::type($path) . ' given', 400);
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

        return $octal === false ? 'd' . $mode : $mode;
    }

    public function getType()
    {
        return 'directory';
    }

    public function getParent()
    {
        return self::getInstance(dirname($this->path));
    }

    public function create($mode = null)
    {
        if ($this->exists()) {
            throw new AeDirectoryException('Cannot create: directory already exists', 412);
        }

        if (!is_writable(dirname($this->path))) {
            throw new AeDirectoryException('Cannot create: parent directory is not writable', 401);
        }

        if (!@mkdir($this->path)) {
            $e = error_get_last();
            throw new AeDirectoryException('Cannot create: ' . $e['message'], 500);
        }

        $this->setPath($this->path);

        if ($mode !== null) {
            $this->setMode($mode);
        }

        return $this;
    }

    public function count()
    {
        return count(scandir($this->path)) - 2;
    }

    public function getHandle()
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot open: directory does not exist', 404);
        }

        if (!$this->isReadable()) {
            throw new AeDirectoryException('Cannot open: permission denied', 403);
        }

        return $this->_open();
    }

    protected function _open()
    {
        if (!$this->_isOpened())
        {
            $this->_handle = @opendir($this->path);

            if (!$this->_handle) {
                $e = error_get_last();
                throw new AeDirectoryException('Cannot open: ' . $e['message'], 500);
            }
        }

        return $this->_handle;
    }

    protected function _close()
    {
        if ($this->_isOpened())
        {
            @closedir($this->_handle);
            $this->_handle = null;
        }

        return $this;
    }

    public function getIterator()
    {
        return AeDirectory_Iterator::getInstance($this);
    }

    public static function getInstance($path)
    {
        $path = self::absolutePath($path);

        return AeInstance::get('AeDirectory', array($path), true, false);
    }
}

class AeDirectoryException extends AeFileException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Directory');
        parent::__construct($message, $code);
    }
}
?>