<?php

class AeDirectory extends AeObject implements AeInterface_File, Countable, IteratorAggregate
{
    public function __construct($path = null)
    {
        if (!is_null($path)) {
            $this->setPath($path);
        }
    }

    public function setPath($path)
    {
        $path = (string) $path;

        if (file_exists($path) && !is_dir($path)) {
            throw new AeDirectoryException('Invalid value passed: expecting directory, file given', 400);
        }

        $this->_path = $this->_getAbsolutePath($path);

        return $this;
    }

    protected function _getAbsolutePath($path)
    {
        if (file_exists($path) && !is_link($path)) {
            return realpath($path);
        }

        return $path;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function exists()
    {
        return file_exists($this->path);
    }

    public function isReadable()
    {
        return is_readable($this->path);
    }

    public function isWritable()
    {
        if ($this->exists()) {
            return is_writable($this->path);
        }

        return $this->parent->isWritable();
    }

    public function isExecutable()
    {
        return is_executable($this->path);
    }

    public function isLink()
    {
        return is_link($this->path);
    }
}
?>