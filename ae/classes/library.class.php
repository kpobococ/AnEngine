<?php
/**
 * Library class file
 *
 * See {@link AeLibrary} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Library class
 *
 * This is the general library class factory. It also contains the library
 * autoload method for the {@link AeAutoload} class.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
abstract class AeLibrary
{
    /**
     * Library factory method
     *
     * Return an instance of the given library. Accepts unlimited number of
     * arguments to pass to the target library constructor/factory method. Usage
     * example:
     *
     * <code>$file = AeLibrary::getInstance('file', 'ae'.SLASH.'foo.txt');
     *
     * echo $file->getClass(); // prints "AeFile_Driver_File"
     * echo $file->getName();  // prints "foo.txt"</code>
     *
     * @param string $name    library name
     * @param mixed  $arg,... unlimited number of arguments for target library
     *                        constructor/factory method.
     *
     * @return object
     */
    public static function getInstance($name)
    {
        $class = 'Ae' . ucfirst($name);
        $args  = func_get_args();
        $args  = array_splice($args, 1);

        try {
            $instance = AeInstance::get($class, $args, true, true);
        } catch (AeInstanceException $e) {
            if ($e->getCode() == 404) {
                throw new AeLibraryException(ucfirst($name) . ' not found', 404);
            }

            throw $e;
        }

        return $instance;
    }
}

/**
 * Library exception class
 *
 * Library-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeLibraryException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Library');
        parent::__construct($message, $code);
    }
}

?>