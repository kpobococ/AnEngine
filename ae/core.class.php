<?php
/**
 * Core class file
 *
 * See {@link AeCore} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Core class
 *
 * This class has some basic static methods, including the {@link AeCore::load()
 * load()} method for framework initialization. It is made static because no
 * instance is required.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeCore
{
    /**
     * Core autoload method
     *
     * This method loads general class and interface files from the
     * ae/classes/ path. Each underscore in the name of the class will be
     * treaded as a subdirectory of the classes directory. File should have
     * .class.[extension] suffix to be loaded.
     *
     * So a class named AeHello_World (or just Hello_World, see {@link
     * AeCore::getClassPath() getClassPath()} method for more details) should
     * have ae/classes/hello/world.class.php as its path to be successfully
     * loaded by this autoload method.
     *
     * @see AeAutoload
     * @see AeAutoload::tryFile()
     *
     * @param string $class class name
     * 
     * @return bool true if the class file has been successfully found and
     *              loaded. False otherwise
     */
    public static function autoload($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return true;
        }

        $file = self::getClassPath($class);

        if (!$file) {
            // Not an internal class
            return false;
        }

        $file = $file . '.class';

        return AeAutoload::findFile($file);
    }

    /**
     * Load and initialize the framework
     *
     * Loads the {@link AeAutoload} class and registers two basic autoload
     * methods:
     *
     * - {@link AeCore::autoload()}
     * - {@link AeException::autoload()}
     *
     * Also calls {@link AeAutoload::detect()} instantly for any custom autoload
     * handlers.
     */
    public static function load()
    {
        if (!defined('SLASH')) {
            /**
             * DIRECTORY_SEPARATOR shortcut
             */
            define('SLASH', DIRECTORY_SEPARATOR);
        }

        set_include_path(realpath(dirname(__FILE__) . SLASH . '..') . PATH_SEPARATOR .
                         get_include_path());

        if (!version_compare(PHP_VERSION, '5.1.2', '>=')) {
            // *** Explicitly include exception file
            include_once dirname(__FILE__) . SLASH . 'classes' . SLASH . 'exception.class.php';
            throw new AeException('AnEngine framework requires PHP version 5.1.2 or later', 503);
        }

        include_once 'ae' . SLASH . 'autoload.class.php';

        AeAutoload::extensions('.php');

        AeAutoload::register(array('AeCore', 'autoload'));
        AeAutoload::register(array('AeException', 'autoload'));

        AeAutoload::detect();
    }

    /**
     * Return path to the class file based on the file name
     *
     * Returns a file path based on a class name passed. If <var>$prefix</var>
     * is found at the beginning of a class name, it is automatically replaced
     * with the <var>$prepend</var> value. This can be used by other
     * applications for their own autoload methods.
     *
     * This method does not return class suffix and/or extension. So a class
     * named Hello_World will be transformed to hello/world.
     *
     * This method is useful for custom autoload handlers.
     *
     * @param string $class   class name
     * @param string $prefix  if this prefix is found, <var>$prepend</var> is prepended to class path
     * @param string $prepend this is prepended to class path if <var>$prefix</var> is found
     * 
     * @return string class file path
     */
    public static function getClassPath($class, $prefix = 'Ae', $prepend = 'ae_classes_')
    {
        if (substr($class, 0, strlen($prefix)) == $prefix) {
            // *** Use ae only if Ae prefix is found
            $class = $prepend . substr($class, strlen($prefix));
        }

        $bits = explode('_', $class);

        foreach ($bits as $i => $bit)
        {
            if (strlen($bit) == 0) {
                continue;
            }

            $bits[$i] = strtolower($bit[0]) . substr($bit, 1);
        }

        $class = implode(SLASH, $bits);

        return $class;
    }
}

?>