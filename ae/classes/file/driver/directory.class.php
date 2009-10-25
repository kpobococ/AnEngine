<?php
/**
 * @todo write documentation
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeFile_Driver_Directory extends AeFile_Driver
{
    protected $_current = null;
    protected $_key     = null;

    public static function getInstance($filepath)
    {
        return AeInstance::get('AeFile_Driver_Directory', array($filepath), true, false);
    }

    public function load($filepath = null)
    {
        if (file_exists($filepath) && !is_dir($filepath)) {
            throw new AeFileDriverDirectoryException('Requested path is not a directory: ' . $filepath);
        }

        $this->_close();

        parent::load($filepath);

        $this->rewind();
    }

    public function read($dots = true)
    {
        $return = scandir($this->getPath());

        if (!$dots)
        {
            $j = 0;

            foreach ($return as $i => $file)
            {
                if ($file == '.' || $file == '..') {
                    unset($return[$i]);
                    $j++;
                }

                if ($j == 2) {
                    return array_values($return);
                }
            }
        }

        return $return;
    }

    public function isDot()
    {
        return ($this->getName() == '.' || $this->getName() == '..');
    }

    public function getName()
    {
        return parent::getName();
    }

    public function getSize($human = false)
    {
        if ($this->_size === null)
        {
            $this->_size = 0;

            foreach ($this as $entry) {
                $this->_size += $entry->getSize();
            }
        }

        return parent::getSize($human);
    }

    public function getParent($object = true)
    {
        if ($this->_parentPath === null) {
            $path = explode(SLASH, $this->getPath());
            array_pop($path);
            $this->_parentPath = realpath(implode(SLASH, $path));
        }

        if (strlen($this->_parentPath) == 0) {
            return false;
        }

        return parent::getParent($object);
    }

    protected function _open()
    {
        if ($this->_isOpened()) {
            return $this->_handle;
        }

        $this->_handle = @opendir($this->getPath());

        if (!$this->_handle) {
            $this->_handle = null;
            return false;
        }

        return $this->_handle;
    }

    protected function _close()
    {
        if ($this->_isOpened()) {
            @closedir($this->_handle);
            $this->_handle     = null;
        }

        return true;
    }

    protected function _isOpened()
    {
        return is_resource($this->_handle);
    }

    public function delete()
    {
        foreach ($this as $file) {
            $file->delete();
        }

        $this->_close();
        return @rmdir($this->getPath());
    }

    public function create($mode = 644)
    {
        if (!$this->exists() && !@mkdir($this->getPath())) {
            return false;
        }

        return parent::create($mode);
    }

    /* ********************************************************************** */
    /* * ITERATOR INTERFACE IMPLEMENTATION                                  * */
    /* ********************************************************************** */
    public function rewind()
    {
        $dh = $this->_open();
        @rewinddir($dh);

        do {
            $this->_current = @readdir($dh);
        } while ($this->_current == '.' || $this->_current == '..');

        if ($this->_current === false) {
            $this->_key     = null;
            $this->_current = null;
            return true;
        }

        $this->_key = 0;

        return true;
    }

    public function current()
    {
        if ($this->_current !== null) {
            return AeFile::getInstance($this->getPath() . SLASH . $this->_current);
        }

        return false;
    }

    public function key()
    {
        return $this->_key;
    }

    public function next()
    {
        $dh = $this->_open();
        $this->_current = @readdir($dh);

        if ($this->_current === false) {
            $this->_key     = null;
            $this->_current = null;
            return false;
        }

        $this->_key++;

        return $this->current(false);
    }
}

/**
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeFileDriverDirectoryException extends AeFileDriverException
{
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Directory');
        parent::__construct($message, $code);
    }
}
?>