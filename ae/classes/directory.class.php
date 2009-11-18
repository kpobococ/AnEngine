<?php
/**
 * @todo write documentation
 * @todo inherit both this and AeFile from a single AeFile_Node abstract class
 * @todo move AeInterface_File implements statement to AeFile_Node class
 * @todo add exceptions for PHP function failures
 */
class AeDirectory extends AeObject implements AeInterface_File, Countable, IteratorAggregate
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
        $path = (string) $path;

        if (file_exists($path) && !is_dir($path)) {
            throw new AeDirectoryException('Invalid value passed: expecting directory, file given', 400);
        }

        // TODO: use AeInstance::clear() to relocate cached instance if path has been changed
        $this->_path = $this->_getAbsolutePath($path);

        return $this;
    }

    protected function _getAbsolutePath($path)
    {
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

    /**
     * Set mode
     *
     * Sets the directory access mode. You can use both octal and symbolic
     * notation:
     * <code> $directory->setMode(0644); // rw for owner, r for group and other
     * $directory->setMode('drw-r--r--'); // same as above</code>
     *
     * Note, that the first character can be ommited, but must be "d", if
     * specified.
     *
     * @throws AeDirectoryException #400 on invalid value
     *
     * @param int|string $mode
     *
     * @return AeDirectory self
     */
    public function setMode($mode)
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot set mode: directory does not exist', 412);
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
                    throw new AeDirectoryException('Invalid value passed: symbolic notation must be 9 or 10 characters, ' . $length . ' given', 400);
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
            throw new AeDirectoryException('Invalid value passed: expecting string or integer, ' . AeType::of($mode) . ' given', 400);
        }

        if (!preg_match('#0?[0-7]{3}#', decoct($mode))) {
            throw new AeDirectoryException('Invalid value passed: value must be an octal integer', 400);
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
     * @return AeDate
     */
    public function getAccessTime()
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot get access time: directory does not exist', 412);
        }

        return new AeDate(@fileatime($this->path));
    }

    public function getModifiedTime()
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot get modified time: directory does not exist', 412);
        }

        return new AeDate(@filemtime($this->path));
    }

    public function getName()
    {
        return @basename($this->path);
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getMode($octal = true)
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot get mode: directory does not exist', 412);
        }

        $mode = fileperms($this->path);

        if ($octal === false)
        {
            $return = 'd';

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
            throw new AeDirectoryException('Cannot get size: directory does not exist', 412);
        }

        $size = 0;

        foreach ($this as $file) {
            $size += $file->getSize();
        }

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

    public function getType()
    {
        // *** SUDDENLY!
        return 'directory';
    }

    public function getOwner($human = false)
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot get owner: directory does not exist', 412);
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
            throw new AeDirectoryException('Cannot get group: directory does not exist', 412);
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
        self::getInstance(dirname($this->path));
    }

    public function touch($time = null)
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot touch: directory does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeDirectoryException('Cannot touch: directory is not writable', 401);
        }

        if ($time === null) {
            $time = time();
        }

        if ($time instanceof AeDate) {
            $time = $time->toInteger()->getValue();
        }

        if (!is_numeric($time)) {
            throw new AeDirectoryException('Invalid time value: expecting numeric or AeDate, ' . AeType::of($time) . ' given', 400);
        }

        @touch($this->path, $time);

        return $this;
    }

    public function rename($name)
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot rename: directory does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeDirectoryException('Cannot rename: directory is not writable', 401);
        }

        if ($name != basename($name)) {
            throw new AeDirectoryException('Invalid name value: name cannot be a path', 400);
        }

        if ($name != $this->name)
        {
            $parent = dirname($this->path);

            if ($this->fireEvent('directory.rename', array($this->name, $name))) {
                $this->_renmov($parent.SLASH.$name);
            }
        }

        return $this;
    }

    public function move($path)
    {
        if (!$this->exists()) {
            throw new AeDirectoryException('Cannot move: directory does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeDirectoryException('Cannot move: directory is not writable', 401);
        }

        $parent = dirname($this->path);

        if ($path != $parent)
        {
            if (!file_exists($path)) {
                throw new AeDirectoryException('Invalid path value: target directory does not exist', 400);
            }

            if (!is_dir($path)) {
                throw new AeDirectoryException('Invalid path value: target is not a directory', 400);
            }

            if ($this->fileEvent('directory.move', array($parent, $path))) {
                $this->_renmov($path.SLASH.$this->name);
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
            throw new AeDirectoryException('Cannot delete: directory does not exist', 412);
        }

        if (!$this->isWritable()) {
            throw new AeDirectoryException('Cannot delete: directory is not writable', 401);
        }

        if ($this->fireEvent('directory.delete')) {
            $this->_delete($this->path);
        }

        return $this;
    }

    protected function _delete($path)
    {
        // TODO: move this function to parent class once ready
        if (!is_dir($path) || is_link($path)) {
            return @unlink($path);
        }

        foreach (scandir($path) as $name)
        {
            if ($name == '.' || $name == '..') {
                continue;
            }

            if (!$this->_delete($path.SLASH.$name)) {
                return false;
            }
        }

        return @rmdir($path);
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

    public function exists()
    {
        return file_exists($this->path);
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
        return AeInstance::get('AeDirectory', array(dirname($path)), true, false);
    }
}

class AeDirectoryException extends AeException
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