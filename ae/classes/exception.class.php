<?php
/**
 * Exception class file
 *
 * See {@link AeException} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Exception class
 *
 * This is the basic exception class for the AnEngine framework.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeException extends Exception
{
    /**
     * An array of prefix bits
     * @var array $prefix
     */
    protected $_prefix = array();

    /**
     * @param string $message exception message
     * @param int    $code    exception code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('AnEngine');
        parent::__construct($message, $code);
    }

    /**
     * Return exception class prefix
     *
     * @return string
     */
    final public function getPrefix()
    {
        return implode('::', array_reverse($this->_prefix)) . ' exception:';
    }

    /**
     * Append exception class prefix
     *
     * @param string $bit
     */
    final protected function _appendPrefix($bit = null)
    {
        if (!is_null($bit)) {
            $this->_prefix[] = $bit;
        }
    }

    /**
     * Exception autoload method
     *
     * This method loads exception class files from the ae/classes/ path.
     * Since most exception classes are declared inside the general class files,
     * this method also converts the class name to the respective general class,
     * to autodetect that class's path:
     * <code> throw new AeFooBarBazException('Not found', 404);
     * // Will search for the AeFoo_Bar_Baz class
     * // inside the ae/classes/foo/bar/baz.class.php file</code>
     *
     * @see AeAutoload
     * @see AeAutoload::extensions()
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

        if (substr($class, -9) != 'Exception' || substr($class, 0, 2) != 'Ae') {
            return false;
        }

        $class = preg_replace('#([a-z])([A-Z])#', '\\1_\\2', substr($class, 2, -9));
        $file  = AeCore::getClassPath('Ae' . $class);

        if (!$file) {
            // Not an internal exception class
            return false;
        }

        $file = $file . '.class';

        return AeAutoload::findFile($file);
    }

    /**
     * Display exception as text
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getPrefix() . ' ' . $this->getMessage() . ' (Code ' . $this->getCode() . ')';
    }
}
?>