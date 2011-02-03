<?php
/**
 * @todo write documentation
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

    public function getName($extension = true)
    {
        $name = parent::getName();

        if ($extension === false) {
            return substr($name, 0, -(strlen($this->extension) + 1));
        }

        return $name;
    }

    public function getMode($octal = true)
    {
        $mode = parent::getMode($octal);

        return $octal === false ? '-' . $mode : $mode;
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

        if (@fwrite($this->_open('wb'), '') === false) {
            $e = error_get_last();
            throw new AeFileException('Cannot create: ' . $e['message'], 500);
        }

        $this->setPath($this->path);

        if ($mode !== null) {
            $this->setMode($mode);
        }

        return $this;
    }

    public function copy($path)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot copy: file does not exist', 412);
        }

        if ($path instanceof AeInterface_File) {
            $path = $path->getPath();
        }

        if (file_exists($path))
        {
            if (!is_dir($path)) {
                throw new AeFileException('Cannot copy: target already exists', 400);
            }

            $path = $path . SLASH . $this->name;
        }

        if ($path == $this->path) {
            throw new AeFileException('Cannot copy: target path matches original path', 400);
        }

        if ($this->fireEvent('copy', array($path)))
        {
            if (!@copy($this->path, $path)) {
                $e = error_get_last();
                throw new AeFileException('Cannot copy: ' . $e['message'], 500);
            }
        }

        return AeFile::getInstance($path);
    }

    public function getHandle($mode = 'r')
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot open: file does not exist', 404);
        }

        return $this->_open($mode);
    }

    public function read($length = null)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot read: file does not exist', 404);
        }

        if ($length === null)
        {
            $return = @file_get_contents($this->path);

            if ($return === false) {
                $e = error_get_last();
                throw new AeFileException('Cannot read: ' . $e['message'], 500);
            }

            return $return;
        }

        if ($length instanceof AeScalar) {
            $length = $length->getValue();
        }

        $type = AeType::of($length);

        if ($type != 'integer') {
            throw new AeFileException('Invalid length value: expecting integer, ' . $type . ' given', 400);
        }

        $fh     = $this->_open('rb');
        $return = @fread($fh, (int) $length);

        if ($return === false) {
            $e = error_get_last();
            throw new AeFileException('Cannot read: ' . $e['message'], 500);
        }

        return $return;
    }

    public function getLine()
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot read: file does not exist', 404);
        }

        $fh = $this->_open('r');

        if (@feof($fh)) {
            return false;
        }

        $line = @fgets($fh);

        if ($line === false) {
            $e = error_get_last();
            throw new AeFileException('Cannot read: ' . $e['message'], 500);
        }

        return $line;
    }

    public function write($string, $length = null)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot write: file does not exist', 404);
        }

        $string = (string) $string;

        if ($length !== null)
        {
            if ($length instanceof AeScalar) {
                $length = $length->getValue();
            }

            $type = AeType::of($length);

            if ($type != 'integer') {
                throw new AeFileException('Invalid length value: expecting integer, ' . $type . ' given', 400);
            }
        }

        if ($this->_isOpened('wb'))
        {
            $fh = $this->_open('wb');

            if (!@ftruncate($fh, 0)) {
                $e = error_get_last();
                throw new AeFileException('Cannot write: ' . $e['message'], 500);
            }

            if (!@rewind($fh)) {
                $e = error_get_last();
                throw new AeFileException('Cannot write: ' . $e['message'], 500);
            }
        } else {
            $fh = $this->_open('wb');
        }

        if ($length !== null) {
            $result = @fwrite($fh, $string, $length);
        } else {
            $result = @fwrite($fh, $string);
        }

        if ($result === false) {
            $e = error_get_last();
            throw new AeFileException('Cannot write: ' . $e['message'], 500);
        }

        return $this;
    }

    public function append($string, $length = null)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot write: file does not exist', 404);
        }

        $string = (string) $string;

        if ($length !== null)
        {
            if ($length instanceof AeScalar) {
                $length = $length->getValue();
            }

            $type = AeType::of($length);

            if ($type != 'integer') {
                throw new AeFileException('Invalid length value: expecting integer, ' . $type . ' given', 400);
            }
        }

        $fh = $this->_open('ab');

        if ($length !== null) {
            $result = @fwrite($fh, $string, $length);
        } else {
            $result = @fwrite($fh, $string);
        }

        if ($result === false) {
            $e = error_get_last();
            throw new AeFileException('Cannot write: ' . $e['message'], 500);
        }

        return $this;
    }

    public function clear()
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot clear: file does not exist', 404);
        }

        if (@fwrite($this->_open('wb'), '') === false) {
            $e = error_get_last();
            throw new AeFileException('Cannot clear: ' . $e['message'], 500);
        }

        return $this;
    }

    public function getExtension()
    {
        return strtolower(substr(strrchr($this->name, '.'), 1));
    }

    protected function _open($mode = 'r')
    {
        if (!$this->_isOpened($mode)) {
            $this->_close();
        }

        if (!$this->_isOpened())
        {
            $this->_handle = @fopen($this->path, $mode);

            if (!$this->_handle) {
                $e = error_get_last();
                throw new AeFileException('Cannot open: ' . $e['message'], 500);
            }

            $this->_handleMode = $mode;
        }

        return $this->_handle;
    }

    protected function _close()
    {
        if ($this->_isOpened())
        {
            if (!@fclose($this->_handle)) {
                $e = error_get_last();
                throw new AeFileError('Cannot close: ' . $e['message'], 500);
            }

            $this->_handle     = null;
            $this->_handleMode = null;
        }

        return $this;
    }

    protected function _isOpened($mode = null)
    {
        $opened = parent::_isOpened();

        if ($opened && $mode !== null) {
            $opened = $this->_handleMode === $mode;
        }

        return $opened;
    }

    /**
     * @return AeFile
     */
    public static function getInstance($path)
    {
        $path = self::absolutePath($path);

        return AeInstance::get('AeFile', array($path), true, false);
    }
}

// *** See AeObject_File ae/classes/object/file.class.php
?>