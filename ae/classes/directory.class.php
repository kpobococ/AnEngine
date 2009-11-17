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
        return new AeDate(@fileatime($this->path));
    }

    public function getModifiedTime()
    {
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
        $owner = @fileowner($this->path);

        if ($human === true && function_exists('posix_getpwuid')) {
            $info  = posix_getpwuid($this->_owner);
            $owner = $info['name'];
        }

        return $owner;
    }

    public function getGroup($human = false)
    {

    }

    public function getParent()
    {

    }

    public function touch($time = null)
    {

    }

    public function rename($name)
    {

    }

    public function move($path)
    {

    }

    public function delete()
    {

    }

    public function create()
    {

    }

    public function exists()
    {
        return file_exists($this->path);
    }

    public function count()
    {

    }

    public function getIterator()
    {
        return new AeDirectory_Iterator($this);
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