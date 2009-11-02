<?php
/**
 * Files library file
 *
 * See {@link AeFile} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Files library
 *
 * This is a basic file wrapper. It provides a standard interface for working
 * with files, directories and links.
 *
 * There are two possible ways of getting an instance of this:
 * <code> $file = AeFile::getInstance('file'); // An undefined file
 * $file->load('path'.SLASH.'to'.SLASH.'file.txt');
 *
 * // This is the same as above
 * $file = AeFile::getInstance('file', 'path'.SLASH.'to'.SLASH.'file.txt');
 *
 * // Autodetect type
 * $file = AeFile::getInstance('path'.SLASH.'to'.SLASH.'file.txt');</code>
 *
 * Note, for autodetection to be executed, filepath must contain at least one
 * {@see SLASH} in it. Otherwise, filepath is assumed to be a driver name,
 * resulting in an exception being thrown.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeFile
{
    const DEFAULT_DRIVER = 'file';

    /**
     * Get file object
     *
     * See {@link AeFile} documentation for details
     *
     * @throws AeFileException #404 if driver not found
     * @throws AeFileException #501 if driver is not an implementation of the
     *                         {@link AeInterface_File} interface
     *
     * @param string $driver  driver name
     * @param mixed  $arg,... unlimited number of arguments to pass to the driver
     *
     * @return AeInterface_File instance of a selected file driver
     */
    public static function getInstance($driver = null)
    {
        if (strpos($driver, SLASH) !== false && file_exists($driver))
        {
            // *** This is definitely a file path
            $args = array($driver);

            if (is_dir($driver)) {
                $driver = 'directory';
            } else {
                $driver = 'file';
            }
        } else {
            $driver = $driver !== null ? $driver : self::DEFAULT_DRIVER;
            $args   = func_get_args();
            $args   = array_splice($args, 1);
        }

        $class = 'AeFile_Driver_' . ucfirst($driver);

        try {
            $instance = AeInstance::get($class, $args, false, true);
        } catch (AeInstanceException $e) {
            if ($e->getCode() == 404) {
                throw new AeFileException(ucfirst($driver) . ' driver not found', 404);
            }

            throw $e;
        }

        if (!($instance instanceof AeInterface_File)) {
            throw new AeFileException(ucfirst($driver) . ' driver has an invalid access interface', 501);
        }

        return $instance;
    }
}

/**
 * Files exception class
 *
 * Files-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
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