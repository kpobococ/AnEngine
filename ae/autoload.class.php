<?php
/**
 * Autoloader class file
 *
 * See {@link AeAutoload} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Autoloader class
 *
 * This class is an autoloader handler class. It allows working with autoload
 * as a stack, using {@link AeAutoload::unshift()}, {@link AeAutoload::shift()},
 * {@link AeAutoload::push()} and {@link AeAutoload::pop()} methods to prepend,
 * remove from the beginning, append and remove from the end respectively any
 * given autoload method callback.
 *
 * The basic {@link AeAutoload::register()} and {@link AeAutoload::unregister()}
 * methods are also available.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeAutoload
{
    /**
     * Register an autoload function
     *
     * Register a new autoload handler function or method, using a callback. If
     * a given callback function or method is already present, it will not be
     * registered again.
     *
     * @uses spl_autoload_register()
     * @see AeAutoload::unregister(), AeAutoload::unshift(), AeAutoload::push()
     *
     * @param callback|AeCallback $callback autoloader function or method
     *                                      callback
     *
     * @return bool
     */
    public static function register($callback)
    {
        if (class_exists('AeCallback', false) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        return spl_autoload_register($callback);
    }

    /**
     * Unregister an autoload function
     *
     * Unregister a registered autoload handler function or method, using its
     * callback. If a given callback function or method is not registered, no
     * error will be generated.
     *
     * @uses spl_autoload_unregister()
     * @see AeAutoload::register(), AeAutoload::shift(), AeAutoload::pop()
     *
     * @param callback|AeCallback $callback autoloader function or method
     *                                      callback
     * 
     * @return bool
     */
    public static function unregister($callback)
    {
        if (class_exists('AeCallback', false) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        return spl_autoload_unregister($callback);
    }

    /**
     * Prepend an autoload function to the beginning of the autoload stack
     *
     * Prepend a new autoload handler function or method to the beginning of the
     * autoload stack, using a callback. If a given callback function or method
     * is already present, it will be moved to the beginning of the stack.
     *
     * @see AeAutoload::shift(), AeAutoload::push(), AeAutoload::register()
     *
     * @param callback|AeCallback $callback autoloader function or method
     *                                      callback
     * 
     * @return int new number of elements in the autoload stack
     */
    public static function unshift($callback)
    {
        if (class_exists('AeCallback', false) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        $stack = self::get();

        self::clear();

        if ($stack) {
            array_unshift($stack, $callback);
        } else {
            $stack = array($callback);
        }

        foreach ($stack as $callback) {
            self::register($callback);
        }

        return count(self::get());
    }

    /**
     * Shift an autoload function from the beginning of an autoload stack
     *
     * Shift an autoload handler function or method from the beginning of an
     * autoload stack and return its callback.
     *
     * @see AeAutoload::unshift(), AeAutoload::pop(), AeAutoload::unregister()
     *
     * @return callback|null callback, or null if the stack was empty
     */
    public static function shift()
    {
        $stack  = self::get();
        $return = array_shift($stack);

        if ($return !== null) {
            self::unregister($return);
        }

        return $return;
    }

    /**
     * Push an autoload function onto the end of an autoload stack
     *
     * Push an autoload handler function or method onto the end of an autoload
     * stack, using a callback. If a given callback function or method is
     * already present, it will be moved to the end of the stack.
     *
     * @see AeAutoload::pop(), AeAutoload::unshift(), AeAutoload::register()
     *
     * @param callback|AeCallback $callback autoloader function or method
     * 
     * @return int new number of elements in the autoload stack
     */
    public static function push($callback)
    {
        if (class_exists('AeCallback', false) && $callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        self::unregister($callback);
        self::register($callback);

        return count(self::get());
    }

    /**
     * Pop the autoload function off the end of an autoload stack
     *
     * Pop the autoload handler function or method off the end of an autoload
     * stack and return its callback.
     *
     * @see AeAutoload::push(), AeAutoload::shift(), AeAutoload::unregister()
     *
     * @return callback|null callback, or null if the stack was empty
     */
    public static function pop()
    {
        $stack  = self::get();
        $return = array_pop($stack);

        if ($return !== null) {
            self::unregister($return);
        }

        return $return;
    }

    /**
     * Return the autoload stack
     *
     * Return the autoload stack as an array of callback values
     *
     * @uses spl_autoload_functions()
     *
     * @return array
     */
    public static function get()
    {
        $return = spl_autoload_functions();

        return is_array($return) ? $return : array();
    }

    /**
     * Call the autoload stack on a class
     *
     * Call all the functions and methods on the autoload stack to find a
     * given class. If a class is already included in the script, the autoload
     * stack will not be called.
     *
     * @uses spl_autoload_call()
     *
     * @param string $class class name
     * 
     * @return bool true if class found, false otherwise
     */
    public static function call($class)
    {
        if (!class_exists($class, false)) {
            spl_autoload_call($class);
        }

        return class_exists($class, false);
    }

    /**
     * Set or get the file extensions
     *
     * Set the file extensions used in the autoload functions. A custom autoload
     * function must provide support for this feature. To get the current list
     * of extensions, <var>$extensions</var> parameter should be ommited.
     *
     * @uses spl_autoload_extensions()
     *
     * @param array|string $extensions an array or a comma separated list of
     *                                 extensions, i.e. (string) '.php, .inc' or
     *                                 array('.php', '.inc')
     * 
     * @return bool|array
     */
    public static function extensions($extensions = null)
    {
        if (is_array($extensions)) {
            $extensions = implode(',', $extensions);
        }

        if ($extensions !== null) {
            spl_autoload_extensions($extensions);
            return true;
        } else {
            return explode(',', spl_autoload_extensions());
        }
    }

    /**
     * Try to find a file
     *
     * Tries to find a class file, based on it's name. This method will use
     * include path to try each of the possible paths and {@link
     * AeAutoload::extensions()} to try each of the supported extensions.
     *
     * @uses AeAutoload::extensions()
     *
     * @param string $file file name and path without extension
     *
     * @return bool
     */
    public static function findFile($file)
    {
        foreach (self::extensions() as $ext)
        {
            foreach (explode(PATH_SEPARATOR, get_include_path()) as $path)
            {
                if (file_exists(realpath($path).SLASH.$file.$ext)) {
                    include_once $file.$ext;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clear the autoload stack
     *
     * Clear the autoload stack, erasing any active autoload handler functions
     * or methods in it. This will also erase all the basic autoload handlers,
     * provided by the {@link AeCore::load()} initialization method.
     *
     * @return bool
     */
    public static function clear()
    {
        $stack = self::get();

        if ($stack && count($stack) > 0)
        {
            foreach ($stack as $callback)
            {
                if (!self::unregister($callback)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Detect custom class type autoloader hook
     *
     * Detect custom class type autoloader hook and include it. An autoloader
     * hook is a file placed inside the ae's subdirectory and called
     * autoload.inc. This file should contain a single autoload registration
     * call, but can contain any php code.
     *
     * <b>Example</b><br>
     * You have a custom database library, called mydb, which
     * uses a different file-naming convention. Place the library inside the
     * mydb subdirectory of the ae directory. Within mydb subdirectory,
     * create a file called autoload.inc. Create an autoload handler function
     * (i.e. mydb_autoload()), which follows the library's naming convention,
     * and place the following autoload registration line below the function:
     *
     * <code>AeAutoload::register('mydb_autoload');</code>
     *
     * This method is called once inside {@link AeCore::load()} method, so it
     * will be automatically registered within every script call. If {@link
     * AeCore::load()} method is called more than once, your file will be
     * included again, so make sure it will not try to redefine an already
     * defined method. An example of such file would be the following code:
     *
     * <code><?php
     * if (!function_exists('mydb_autoload'))
     * {
     *     function mydb_autoload($class)
     *     {
     *         if (class_exists($class, false) || interface_exists($class, false)) {
     *             return true;
     *         }
     *
     *         if (!$file = AeCore::getClassPath($class)) {
     *             // Not an internal class
     *             return false;
     *         }
     *
     *         $file = 'ae' . SLASH . 'classes' . SLASH . $file;
     *
     *         // *** To provide support for AeAutoload::extensions() method
     *         foreach (AeAutoload::extensions() as $ext)
     *         {
     *             if (file_exists($file.$ext)) {
     *                 include_once $file.$ext;
     *                 return true;
     *             }
     *         }
     *
     *         return false;
     *     }
     * }
     *
     * AeAutoload::register('mydb_autoload');
     * </code>
     *
     * @param string $path path to scan
     */
    public static function detect($path = null)
    {
        if ($path === null) {
            $path = dirname(__FILE__);
        }

        $list = scandir($path);

        foreach ($list as $file)
        {
            if (!is_dir($path . SLASH . $file)) {
                continue;
            }

            if ($file == '..' || $file == '.') {
                continue;
            }

            if (file_exists($path . SLASH . $file . SLASH . 'autoload.inc')) {
                include $path . SLASH . $file . SLASH . 'autoload.inc';
            }
        }
    }
}