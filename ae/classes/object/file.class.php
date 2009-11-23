<?php

abstract class AeObject_File extends AeObject implements AeInterface_File
{
    protected $_path = null;

    public function __construct($path = null)
    {
        if (!is_null($path)) {
            $this->setPath($path);
        }
    }

    public function setPath($path)
    {
        $path = self::absolutePath($path);

        $this->_path = $path;

        return $this;
    }

    /**
     * Set mode
     *
     * Sets the file access mode. You can use both octal and symbolic
     * notation:
     * <code> $file->setMode(0644); // rw for owner, r for group and other
     * $file->setMode('-rw-r--r--'); // same as above</code>
     *
     * Note, that symbolic notation supports 9 and 10 characters, but the first
     * character of a 10 character string is always ignored.
     *
     * @throws AeFileException #400 on invalid value
     * @throws AeFileException #412 if file does not exist
     *
     * @param int|string $mode
     *
     * @return AeObject_File self
     */
    public function setMode($mode)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot set mode: file does not exist', 412);
        }

        if ($mode instanceof AeScalar) {
            $mode = $mode->getValue();
        }

        if (is_string($mode))
        {
            $length = strlen($mode);

            if (!is_numeric($mode))
            {
                // *** Symbolic notation
                if ($length < 9 || $length > 10) {
                    throw new AeFileException('Invalid value passed: symbolic notation must be 9 or 10 characters, ' . $length . ' given', 400);
                }

                if ($length == 10) {
                    $mode = substr($mode, 1);
                }

                $chmod = '0';

                for ($i = 0; $i < 3; $i++)
                {
                    $mask = substr($mode, $i * 3, 3);
                    $bits = 0;

                    $bits += $mask[0] == 'r' ? 4 : 0;
                    $bits += $mask[1] == 'w' ? 2 : 0;
                    $bits += $mask[2] == 'x' ? 1 : 0;

                    $chmod .= $bits;
                }

                $mode = octdec($chmod);
            } else {
                // *** Numeric string
                $mode = octdec($mode);
            }
        }

        if (!is_integer($mode)) {
            throw new AeFileException('Invalid value passed: expecting string or integer, ' . AeType::of($mode) . ' given', 400);
        }

        if (!preg_match('#0?[0-7]{3}#', decoct($mode))) {
            throw new AeFileException('Invalid value passed: value must be an octal integer', 400);
        }

        @chmod($this->path, $mode);
        @clearstatcache();

        return $this;
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

    /**
     * Get last access time
     *
     * Returns date and time the file has been accessed last on.
     *
     * <b>NOTE:</b> The atime of a file is supposed to change whenever the data
     * blocks of a file are being read. This can be costly performance-wise when
     * an application regularly accesses a very large number of files or
     * directories. Some Unix filesystems can be mounted with atime updates
     * disabled to increase the performance of such applications. On such
     * filesystems this function will be useless.
     *
     * @throws AeFileException #412 if file does not exist
     *
     * @return AeDate
     */
    public function getAccessTime()
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot get access time: file does not exist', 412);
        }

        return new AeDate(@fileatime($this->path));
    }

    public function getModifiedTime()
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot get modified time: file does not exist', 412);
        }

        return new AeDate(@filemtime($this->path));
    }

    public function getName()
    {
        var_dump($this->path);
        return @basename($this->path);
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getMode($octal = true)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot get mode: file does not exist', 412);
        }

        $mode = fileperms($this->path);

        if ($octal === false)
        {
            // *** Owner
            $return .= ($mode & 0400) ? 'r' : '-';
            $return .= ($mode & 0200) ? 'w' : '-';
            $return .= ($mode & 0100) ? 'x' : '-';

            // *** Group
            $return .= ($mode & 040) ? 'r' : '-';
            $return .= ($mode & 020) ? 'w' : '-';
            $return .= ($mode & 010) ? 'x' : '-';

            // *** Other
            $return .= ($mode & 04) ? 'r' : '-';
            $return .= ($mode & 02) ? 'w' : '-';
            $return .= ($mode & 01) ? 'x' : '-';

            return $return;
        }

        return substr(sprintf('%o', $mode), -4);
    }

    public function getSize($human = false)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot get size: file does not exist', 412);
        }

        $size = $this->_getSize();

        if ($human === true)
        {
            // *** I doubt that values higher than GiB will occur, but let it stay
            $suffix = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
            $e      = floor(log($size, 1024));
            $size   = round($size / pow(1024, $e), 2);

            if ($e > 0) {
                $size = number_format($size, 2, '.', ' ') . ' ' . $suffix[$e];
            }
        }

        return $size;
    }

    abstract protected function _getSize();

    public function getType()
    {
        return self::type($this->path);
    }

    public function getOwner($human = false)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot get owner: file does not exist', 412);
        }

        $owner = @fileowner($this->path);

        if ($human === true && function_exists('posix_getpwuid')) {
            $info  = posix_getpwuid($owner);
            $owner = $info['name'];
        }

        return $owner;
    }

    public function getGroup($human = false)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot get group: file does not exist', 412);
        }

        $group = @filegroup($this->path);

        if ($human === true && function_exists('posix_getgrgid')) {
            $info  = posix_getgrgid($group);
            $group = $info['name'];
        }

        return $group;
    }

    public function getParent()
    {
        return self::wrap(dirname($this->path));
    }

    public function touch($time = null)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot touch: file does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeFileException('Cannot touch: file is not writable', 401);
        }

        if ($time === null) {
            $time = time();
        }

        if ($time instanceof AeDate) {
            $time = $time->toInteger()->getValue();
        }

        if (!is_numeric($time)) {
            throw new AeFileException('Invalid time value: expecting numeric or AeDate, ' . AeType::of($time) . ' given', 400);
        }

        @touch($this->path, $time);

        return $this;
    }

    public function rename($name)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot rename: file does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeFileException('Cannot rename: file is not writable', 401);
        }

        if ($name != basename($name)) {
            throw new AeFileException('Invalid name value: name cannot be a path', 400);
        }

        if ($name != $this->name && $this->fireEvent('rename', array($this->name, $name))) {
            $this->_renmov(dirname($this->path) . SLASH . $name);
        }

        return $this;
    }

    public function move($path)
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot move: file does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeFileException('Cannot move: file is not writable', 401);
        }

        $parent = dirname($this->path);

        if ($path != $parent)
        {
            if (!file_exists($path)) {
                throw new AeFileException('Invalid path value: target directory does not exist', 400);
            }

            if (!is_dir($path)) {
                throw new AeFileException('Invalid path value: target is not a directory', 400);
            }

            if ($this->fileEvent('move', array($parent, $path))) {
                $this->_renmov($path . SLASH . $this->name);
            }
        }

        return $this;
    }

    protected function _renmov($path)
    {
        $return = @rename($this->path, $path);

        if ($return !== false) {
            $this->setPath($path);
        }

        return $this;
    }

    public function delete()
    {
        if (!$this->exists()) {
            throw new AeFileException('Cannot delete: file does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeFileException('Cannot delete: file is not writable', 401);
        }

        if ($this->fireEvent('delete')) {
            $this->_delete($this->path);
        }

        return $this;
    }

    protected function _delete($path)
    {
        if (!is_dir($path) || is_link($path))
        {
            if (!@unlink($path)) {
                $e = error_get_last();
                throw new AeFileException('Delete failed:' . $e['message'], 500);
            }

            return $this;
        }

        foreach (scandir($path) as $name)
        {
            if ($name == '.' || $name == '..') {
                continue;
            }

            $this->_delete($path . SLASH . $name);
        }

        if (!@rmdir($path)) {
            $e = error_get_last();
            throw new AeFileException('Delete failed:' . $e['message'], 500);
        }

        return $this;
    }

    public function exists()
    {
        return file_exists($this->path);
    }

    public static function absolutePath($path)
    {
        $type = AeType::of($path);

        if ($type != 'string') {
            throw new AeFileException('Invalid path value: expecting string, ' . $type . ' given', 400);
        }

        $path = (string) $path;

        if (file_exists($path) && !is_link($path)) {
            return realpath($path);
        }

        // *** Fix slashes
        $path   = str_replace(array('/', '\\'), SLASH, $path);
        $bits   = array_filter(explode(SLASH, $path), 'strlen');
        $length = count($bits);
        $return = array();

        if ($bits[0] == '.') {
            // *** Expand leading dot to cwd
            $return = explode(SLASH, getcwd());
        }

        foreach ($bits as $bit)
        {
            if ($bit == '.') {
                continue;
            }

            if ($bit == '..') {
                array_pop($return);
            } else {
                $return[] = $bit;
            }
        }

        return implode(SLASH, $return);
    }

    public static function type($of)
    {
        if (is_object($of))
        {
            if ($of instanceof AeObject_File) {
                return self::type($of->path);
            }

            if ($of instanceof AeInterface_File) {
                return $of->getType();
            }
        }

        if (is_string($of) && file_exists($of))
        {
            if (is_dir($of)) {
                return 'directory';
            }

            if (is_file($of)) {
                return 'file';
            }

            return @filetype($of);
        }

        throw new AeFileException('Invalid value passed: expecting file or path, object given', 400);
    }

    public static function wrap($file)
    {
        if ($file instanceof AeString) {
            $file = (string) $file;
        }

        if (is_object($file) && $file instanceof AeObject_File) {
            return $file;
        }

        $type = self::type($file);

        if ($type == 'directory') {
            return AeInstance::get('AeDirectory', array($file), true, true);
        }

        if ($type == 'file') {
            return AeInstance::get('AeFile', array($file), true, true);
        }

        throw new AeFileException('Invalid value passed: expection file or path, ' . AeType::of($file) . ' given', 400);
    }
}

class AeFileException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('File');
        parent::__construct($message, $code);
    }
}
?>