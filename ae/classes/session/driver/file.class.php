<?php
/**
 * File session driver class file
 *
 * See {@link AeSession_Driver_File} class documentation
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * File session driver class
 *
 * This driver uses files as storage for sessions. The reason to have this
 * driver instead of using PHP's standard sessions is the ability to specify
 * where the session files should be stored
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeSession_Driver_File extends AeSession_Driver
{
    /**
     * Session storage path
     * @var string
     */
    protected $_storagePath;

    /**
     * Session file extension
     * @var string
     */
    protected $_extension;

    /**
     * Constructor
     *
     * Sets all the session connection driver options. The available options
     * are:
     * - path      string session storage path;
     * - extension string session file extension;
     *
     * @param AeArray $options
     */
    public function __construct(AeArray $options = null)
    {
        if (isset($options['path']) && file_exists((string) $options['path'])) {
            $this->_storagePath = (string) $options['path'];
        } else {
            $this->_storagePath = getcwd() . SLASH . 'sessions';
        }

        if (isset($options['extension'])) {
            $this->_extension = (string) $options['extension'];
        } else {
            $this->_extension = 'sess';
        }

        parent::__construct($options);
    }

    /**
     * Garbage collector method
     *
     * This is executed when the session garbage collector is executed and takes
     * the max session lifetime as its only parameter
     *
     * @param int $lifetime max session lifetime in seconds
     *
     * @return bool
     */
    public function clean($lifetime)
    {
        $sessions = AeFile::getInstance('directory', $this->_storagePath);
        $expired  = time() - $lifetime;

        foreach ($sessions as $session)
        {
            if (!$session->isFile() || $session->getExtension() != $this->_extension) {
                continue;
            }

            if ($session->getMTime() < $expired) {
                $session->delete();
            }
        }

        return true;
    }

    /**
     * Destroy method
     *
     * This is executed when a session is destroyed with {@link
     * http://php.net/session_destroy session_destroy()} and takes the session
     * id as its only parameter.
     *
     * @param string $id
     *
     * @return bool
     */
    public function destroy($id)
    {
        $session = AeFile::getInstance('file', $this->_storagePath . SLASH . $id . '.' . $this->_extension);

        if (!$session->exists()) {
            return true;
        }

        return $session->delete();
    }

    /**
     * Read method
     *
     * Always returns string value to make save handler works as expected.
     * Returns empty string if there is no data to read
     *
     * @param string $id
     *
     * @return string
     */
    public function read($id)
    {
        $session = AeFile::getInstance('file', $this->_storagePath . SLASH . $id . '.' . $this->_extension);

        if (!$session->exists()) {
            return '';
        }

        return $session->read();
    }

    /**
     * Write method
     *
     * Saves session data, takes session id and data to be save as its
     * parameters. The "write" handler is not executed until after the output
     * stream is closed. Thus, output from debugging statements in the "write"
     * handler will never be seen in the browser. If debugging output is
     * necessary, it is suggested that the debug output be written to a file
     * instead
     *
     * @param string $id
     * @param string $data
     *
     * @return bool
     */
    public function write($id, $data)
    {
        $session = AeFile::getInstance('file', $this->_storagePath . SLASH . $id . '.' . $this->_extension);

        $session->clear();
        return $session->write($data);
    }
}

/**
 * File session driver exception class
 *
 * File session driver-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeSessionDriverFileException extends AeSessionDriverException
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