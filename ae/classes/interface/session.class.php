<?php
/**
 * Session interface file
 *
 * See {@link AeInterface_Session} interface documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */

/**
 * Session interface
 *
 * This is a common session driver interface. All session drivers must
 * implement it.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */
interface AeInterface_Session
{
    /**
     * Constructor
     *
     * @param array $options Session handler options
     */
    public function __construct(AeArray $options = null);

    /**
     * Register handler
     *
     * Register the functions of this class with PHP's session handler
     *
     * @return bool
     */
    public function register();

    /* ********************************************************************** */
    /* * PHP SESSION HANDLER METHODS                                        * */
    /* ********************************************************************** */

    /**
     * Garbage collector
     *
     * Cleans up expired sessions
     *
     * @param int $lifetime maximum session lifetime
     *
     * @return bool
     */
    public function clean($lifetime);

    /**
     * Close session
     *
     * Closes the session handler
     *
     * @return bool
     */
    public function close();

    /**
     * Destroy session
     *
     * Destroy the data for a particular session identifier
     *
     * @param string $id
     *
     * @return bool
     */
    public function destroy($id);

    /**
     * Open session
     *
     * Opens the session handler
     *
     * @param string $path session save path
     * @param string $name session name
     *
     * @return bool
     */
    public function open($path, $name);

    /**
     * Read session data
     *
     * Read the data for a particular session identifier
     *
     * @param string $id
     *
     * @return string session data
     */
    public function read($id);

    /**
     * Write session data
     *
     * Write the data for a particular session identifier
     *
     * @param string $id
     * @param string $data
     *
     * @return bool
     */
    public function write($id, $data);
}

?>