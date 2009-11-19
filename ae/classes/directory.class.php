<?php
/**
 * @todo write documentation
 * @todo inherit both this and AeFile from a single AeFile_Node abstract class
 * @todo move AeInterface_File implements statement to AeFile_Node class
 * @todo add exceptions for PHP function failures
 */
class AeDirectory extends AeFile_Node implements Countable, IteratorAggregate
{
    protected $_path = null;

    public function setPath($path)
    {
        if (file_exists($path) && !is_dir($path)) {
            throw new AeDirectoryException('Invalid value passed: expecting directory, file given', 400);
        }

        parent::setPath($this);

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

    protected function _getSize()
    {
        $size = 0;

        foreach ($this as $file) {
            $size += $file->getSize();
        }

        return $size;
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

        @mkdir($this->path);

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

    public function getIterator()
    {
        return new AeDirectory_Iterator($this);
    }

    public static function getInstance($path)
    {
        $path = self::absolutePath($path);

        return AeInstance::get('AeDirectory', array($path), true, false);
    }
}

class AeDirectoryException extends AeFileNodeException
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