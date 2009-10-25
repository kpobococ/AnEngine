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
class AeFile_Driver_File extends AeFile_Driver
{
    protected $_extension  = null;
    protected $_handleMode = null;
    protected $_lineBuffer  = null;
    protected $_currentLine = null;
    protected $_clearedLine = null;

    public static function getInstance($filepath)
    {
        $ext   = strtolower(substr(strrchr($filepath, '.'), 1));
        $class = 'AeFile_Driver_File_' . ucfirst($ext);

        if (!class_exists($class)) {
            $class = 'AeFile_Driver_File';
        }

        if (file_exists($filepath)) {
            $filepath = realpath($filepath);
        }

        try {
            $instance = AeInstance::get($class, array($filepath), false, false);
        } catch (AeInstanceException $e) {
            if ($e->getCode() == 404) {
                throw new AeFileDriverFileException(ucfirst($driver) . ' driver not found', 404);
            }

            throw $e;
        }

        if (!($instance instanceof AeInterface_File)) {
            throw new AeFileDriverFileException(ucfirst($driver) . ' driver has an invalid access interface', 501);
        }

        return $instance;
    }

    public function load($filepath = null)
    {
        if (file_exists($filepath) && !is_file($filepath)) {
            throw new AeFileDriverFileException('Requested path is not a file: ' . $filepath);
        }

        $this->_close();

        parent::load($filepath);

        $this->_extension = null;

        $this->fireEvent('load', array());
    }

    public function read($length = null)
    {
        $fh = $this->_open('r');

        if ($length !== null) {
            return @fread($fh, (int) $length);
        }

        return @fread($fh, $this->getSize());
    }

    public function write($string, $length = null)
    {
        $fh = $this->_open('w');

        if (!$fh) {
            return false;
        }

        if (!@rewind($fh)) {
            return false;
        }

        if ($length !== null) {
            return @fwrite($fh, $string, $length);
        }

        return @fwrite($fh, $string);
    }

    public function append($string, $length = null)
    {
        $fh = $this->_open('r+');
        @fseek($fh, 0, SEEK_END);

        if ($length !== null) {
            return @fwrite($fh, $string, $length);
        }

        return @fwrite($fh, $string);
    }

    public function clear()
    {
        return $this->write('');
    }

    public function copy($path, $extension = true)
    {
        if (!$extension) {
            $path .= '.' . $this->getExtension();
        }

        if ($path == $this->getPath()) {
            return false;
        }

        if (@copy($this->getPath(), $path)) {
            return AeFile::getInstance('file', $path);
        }

        return false;
    }

    public function getName($extension = true)
    {
        if (!$extension) {
            return substr($this->_name, 0, -(strlen($this->getExtension()) + 1));
        }

        return parent::getName();
    }

    public function getExtension()
    {
        if ($this->_extension === null) {
            $this->_extension = strtolower(substr(strrchr($this->getName(), '.'), 1));
        }

        return $this->_extension;
    }

    public function getSize($human = false)
    {
        $this->_size = @filesize($this->getPath());

        if (!$this->_size) {
            // TODO: throw exception here
            return false;
        }

        return parent::getSize($human);
    }

    public function getParent($object = true)
    {
        if ($this->_parentPath === null) {
            $this->_parentPath = dirname($this->getPath());
        }

        return parent::getParent($object);
    }

    protected function _open($mode = 'r+')
    {
        if ($this->_isOpened($mode)) {
            return $this->_handle;
        }

        if ($this->_isOpened() && $this->_handleMode != $mode) {
            $this->_close();
        }

        $this->_handle = @fopen($this->getPath(), $mode);

        if (!$this->_handle) {
            $this->_handle     = null;
            $this->_handleMode = null;
            return false;
        }

        $this->_handleMode = $mode;
        return $this->_handle;
    }

    protected function _close()
    {
        if ($this->_isOpened()) {
            @fclose($this->_handle);
            $this->_handle     = null;
            $this->_handleMode = null;
        }

        return true;
    }

    protected function _isOpened($mode = null)
    {
        if ($mode != null) {
            return is_resource($this->_handle) && $this->_handleMode == $mode;
        }

        return is_resource($this->_handle);
    }

    public function rename($name, $extension = true)
    {
        if (!$extension) {
            $name .= '.' . $this->getExtension();
        }

        return parent::rename($name);
    }

    public function create($mode = 755)
    {
        if (!$this->exists() && !$this->_open('x')) {
            return false;
        }

        return parent::create($mode);
    }

    public function setExtension($extension)
    {
        if ($this->getExtension() == $extension) {
            return true;
        }

        return $this->rename($this->getName(false).'.'.$extension);
    }

    /* ********************************************************************** */
    /* * ITERATOR INTERFACE IMPLEMENTATION                                  * */
    /* ********************************************************************** */
    // TODO: move per-line file read to separate method and disable iterator
    public function rewind()
    {
        $fh     = $this->_open('r');
        $return = @rewind($fh);

        $this->_currentLine = 0;

        return $return ? !@feof($fh) : false;
    }

    public function current()
    {
        $this->_checkLineBuffer();

        return isset($this->_lineBuffer[$this->_currentLine]) ? $this->_lineBuffer[$this->_currentLine] : false;
    }

    public function key()
    {
        $this->_checkLineBuffer();

        return isset($this->_lineBuffer[$this->_currentLine]) ? $this->_currentLine : null;
    }

    public function next()
    {
        $this->_currentLine++;
        $this->_checkLineBuffer();

        if (!isset($this->_lineBuffer[$this->_currentLine])) {
            return false;
        }

        return $this->_lineBuffer[$this->_currentLine];
    }

    protected function _checkLineBuffer()
    {
        if (is_array($this->_lineBuffer) && isset($this->_lineBuffer[$this->_currentLine + 1])) {
            return true;
        }

        if ($this->_clearedLine === null) {
            $this->_clearedLine = 0;
        }

        if ($this->_currentLine > $this->_clearedLine)
        {
            // *** Clear unused buffered lines
            for ($i = $this->_clearedLine; $i < $this->_currentLine; $i++) {
                $this->_lineBuffer[$i] = null;
                $this->_clearedLine    = $i;
            }
        }

        if (!is_array($this->_lineBuffer)) {
            $this->_lineBuffer = array();
        }

        do
        {
            $chunk = (string) $this->read(8192);

            if ($chunk == '') {
                break;
            }

            $lines = explode("\n", $chunk);

            if (is_array($lines))
            {
                if (isset($this->_lineBuffer[$this->_currentLine]) && !isset($this->_lineBuffer[$this->_currentLine + 1])) {
                    // *** Current line might be unfinished, add to it
                    $this->_lineBuffer[$this->_currentLine] = $this->_lineBuffer[$this->_currentLine] . array_shift($lines);
                }

                foreach ($lines as $line) {
                    $this->_lineBuffer[] = $line;
                }
            }
        } while (!isset($this->_lineBuffer[$this->_currentLine + 1]));

        return true;
    }
}

/**
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeFileDriverFileException extends AeFileDriverException
{
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('File');
        parent::__construct($message, $code);
    }
}
?>