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
abstract class AeFile_Driver extends AeObject implements AeInterface_File
{
    protected $_name;
    protected $_path;
    protected $_type;
    protected $_mode = null;
    protected $_size = null;
    protected $_owner = null;
    protected $_group = null;
    protected $_inode = null;
    protected $_parentPath = null;
    protected $_parent = null;
    protected $_handle = null;

    public function __construct($filepath)
    {
        $this->load($filepath);
    }

    public function load($filepath = null)
    {
        $this->_path = file_exists($filepath) ? realpath($filepath) : $filepath;
        $this->_name = basename($this->_path);

        // Reset some info
        $this->_mode = null;
        $this->_size = null;

        $this->_owner = null;
        $this->_group = null;
        $this->_inode = null;

        $this->_parentPath = null;
        $this->_parent     = null;
    }

    public function isReadable()
    {
        return is_readable($this->getPath());
    }

    public function isWritable()
    {
        return is_writable($this->getPath());
    }

    public function isExecutable()
    {
        return is_executable($this->getPath());
    }

    public function isFile()
    {
        return is_file($this->getPath());
    }

    public function isDirectory()
    {
        return is_dir($this->getPath());
    }

    public function isLink()
    {
        return is_link($this->getPath());
    }

    public function isDot()
    {
        return false;
    }

    public function isEmpty()
    {
        return $this->getSize() == 0;
    }

    public function getATime()
    {
        return fileatime($this->getPath());
    }

    public function getMTime()
    {
        return filemtime($this->getPath());
    }

    public function getCTime()
    {
        return filectime($this->getPath());
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getMode()
    {
        if ($this->_mode === null) {
            $this->_mode = substr(sprintf('%o', fileperms($this->getPath())), -4);
        }

        return $this->_mode;
    }

    public function getSize($human = false)
    {
        if (!$human || $this->_size <= 0) {
            return $this->_size;
        }

        // *** I doubt that values higher than GiB will occur, but let it stay
        $suffix = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        $e      = floor(log($this->_size, 1024));
        $return = round($this->_size / pow(1024, $e), 2);

        if ($e == 0) {
            return $return;
        }

        return number_format($return, 2, '.', ' ') . ' ' . $suffix[$e];
    }

    public function getType()
    {
        if ($this->_type === null) {
            $this->_type = filetype($this->getPath());
        }

        return $this->_type;
    }

    public function getOwner($human = false)
    {
        if ($this->_owner === null) {
            $this->_owner = fileowner($this->getPath());
        }

        if ($human && function_exists('posix_getpwuid')) {
            $info = posix_getpwuid($this->_owner);

            return $info['name'];
        }

        return $this->_owner;
    }

    public function getGroup($human = false)
    {
        if ($this->_group === null) {
            $this->_group = filegroup($this->getPath());
        }

        if ($human && function_exists('posix_getgrgid')) {
            $info = posix_getgrgid($this->_group);

            return $info['name'];
        }

        return $this->_group;
    }

    public function getINode()
    {
        if ($this->_inode === null) {
            $this->_inode = fileinode($this->getPath());
        }

        return $this->_inode;
    }

    public function getParent($object = true)
    {
        if ($object && $this->_parentPath)
        {
            if ($this->_parent === null) {
                $this->_parent = AeFile::getInstance($this->_parentPath);
            }

            return $this->_parent;
        }

        return $this->_parentPath;
    }

    abstract protected function _open();
    abstract protected function _close();
    abstract protected function _isOpened();

    public function touch($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        if ($this->isWritable()) {
            return @touch($this->getPath(), $time);
        }

        return false;
    }

    public function rename($name)
    {
        // *** Ensure the correct slash direction
        if (SLASH != '/') {
            $name = str_replace('/', SLASH, $name);
        }

        // *** Strip everything before the actual name
        if (strpos($name, SLASH)) {
            $name = substr(strrchr($name, SLASH), 1);
        }

        if ($name == $this->getName()) {
            return $this;
        }

        return $this->_renmov($this->getParent(false).SLASH.$name);
    }

    public function move($path)
    {
        if ($path.SLASH.$this->getName() == $this->getPath()) {
            return $this;
        }

        return $this->_renmov($path.SLASH.$this->getName());
    }

    protected function _renmov($path)
    {
        if ($path == $this->getPath()) {
            return $this;
        }

        $return = @rename($this->getPath(), $path);

        if (!$return) {
            return false;
        }

        $this->load($path);

        return $this;
    }

    public function setMode($mode)
    {
        $mode = substr($mode, -3);
        $mode = '0'.$mode;

        if ($this->getMode() == $mode) {
            return true;
        }

        $return = @chmod($this->getPath(), octdec($mode));

        if ($return) {
            $this->_mode = null;
        }

        return $return;
    }

    public function exists()
    {
        return file_exists($this->getPath());
    }

    public function delete()
    {
        $this->_close();
        return @unlink($this->getPath());
    }

    public function create($mode = null)
    {
        $this->load($this->getPath());

        return $this->setMode($mode);
    }

    public function __destruct()
    {
        $this->_close();
    }

    public function valid()
    {
        return ($this->key() !== null);
    }
}

/**
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeFileDriverException extends AeFileException
{
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Driver');
        parent::__construct($message, $code);
    }
}
?>