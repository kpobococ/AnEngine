<?php
/**
 * Image class file
 *
 * See {@link AeImage} class documentation.
 *
 * @requires GD library to be available on the server.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Image class
 *
 * This class simplifies working with images. It uses GD library to enable you
 * to introduce various modifications to your image. This class represents an
 * image resource, which can be loaded from a number of sources, like jpg, png
 * etc.
 *
 * See {@link AeImage::__construct()} for more details
 *
 * @todo refactor
 * @todo add exceptions for failed php function calls
 *
 * @requires GD library to be available on the server.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeImage extends AeObject
{
    /**
     * Image resource identifier
     * @var resource
     */
    protected $_image = null;

    /**
     * Image file object instance
     * @var AeInterface_File
     */
    protected $_file = null;

    /**
     * Constructor
     *
     * You can create an AeImage object by passing either path to image file or
     * an array of file info from $_FILES array as a source:
     * <code> // Path to image:
     * $img1 = new AeImage('foo.jpg');
     *
     * // Array of file info from file upload:
     * $img2 = new AeImage($_FILES['foo']);</code>
     *
     * You can also pass the two different source types in their respective data
     * type wrappers - AeString and AeArray respectively:
     * <code> // Path to image:
     * $img1 = new AeImage(new AeString('foo.jpg'));
     *
     * // Array of file info from file upload:
     * $files = new AeArray($_FILES);
     * $img2  = new AeImage($files['foo']);</code>
     *
     * @throws AeImageException #503 if gd library is not available
     * @throws AeImageException #400 if source value is invalid
     * @throws AeImageException #404 if source is not found
     *
     * @param AeArray|AeString|array|string $source source image file
     */
    public function __construct($source = null)
    {
        if (!extension_loaded('gd')) {
            throw new AeImageException('GD library is not available', 503);
        }

        if ($source !== null)
        {
            if ($source instanceof AeArray || $source instanceof AeString) {
                $source = $source->getValue();
            }

            if (is_array($source))
            {
                if (!isset($source['tmp_name']) && !isset($source['name'])) {
                    throw new AeImageException('Source value invalid', 400);
                }

                $source = isset($source['tmp_name']) ? $source['tmp_name'] : $source['name'];
            }

            if (!is_string($source)) {
                throw new AeImageException('Source value invalid', 400);
            }

            if (!file_exists($source)) {
                throw new AeImageException('Source file not found', 404);
            }

            $this->setFile(AeFile::absolutePath($source));
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        // *** Destroy the associated file object
        $this->setFile(null);

        if (is_resource($this->_image)) {
            @imagedestroy($this->_image);
        }
    }

    /**
     * Get image size
     *
     * Returns image size info wrapped in {@link AeArray} class instance:
     * <code> $image = new AeImage('photo.jpg');
     * print_r($image->getSize()->getValue());</code>
     * The above code will output something like the following:
     * <pre> Array
     * (
     *     [width] => 800
     *     [height] => 600
     * )</pre>
     *
     * @throws AeImageException #400 if no image resource or file is available
     * @throws AeImageException #500 if internal PHP function fails to return
     *                               the image dimensions. This usually means
     *                               that the image type is not supported
     *
     * @return AeArray
     */
    public function getSize()
    {
        if (is_resource($this->_image)) {
            $info = array(@imagesx($this->_image), @imagesy($this->_image));
        } else {
            if ($this->file === null) {
                throw new AeImageException('No image is available', 400);
            }

            $info = @getimagesize($this->file->path);
        }

        if (!is_array($info)) {
            // *** Try and load an image using external driver
            $info = array(@imagesx($this->getImage()), @imagesy($this->getImage()));
        }

        if (!is_array($info) || !is_int($info[0]) || !is_int($info[1])) {
            throw new AeImageException('Could not detect image dimensions', 500);
        }

        return new AeArray(array(
            'width'  => $info[0],
            'height' => $info[1]
        ));
    }

    /**
     * @todo implement method
     */
    public function resize() {}

    /**
     * Save image
     *
     * Saves an image to a file, specified by the <var>$path</var> argument. If
     * no path argument is given, current path is used, if available:
     * <code> $img = new AeImage('photo.jpg');
     * $img->save('new.jpg');
     * $img->save(); // Saves to photo.jpg
     * $img->setFile(null);
     * $img->save(); // Throws an exception</code>
     *
     * This method also accepts different parameters depending on the image type:
     * <code> $img = new AeImage('photo.jpg');
     * $img->save('photo.jpg', 75); // See {@link php.net/imagejpeg imagejpeg()} function documentation
     * $img->save('photo.png', 9, PNG_ALL_FILTERS); // See {@link php.net/imagepng imagepng()} function documentation</code>
     *
     * @throws AeImageException #400 if no path value passed and no file associated
     * @throws AeImageException #500 if internal PHP image function fails
     *
     * @uses AeImage::_output() to save the image
     *
     * @param AeString|string $path
     *
     * @return AeImage
     */
    public function save($path = null)
    {
        if ($path instanceof AeString) {
            $path = $path->getValue();
        }

        if (is_string($path)) {
            $this->setFile(AeFile::getInstance($path));
        } else if (!is_object($this->file)) {
            throw new AeImageException('No path value passed', 400);
        }

        $args = func_get_args();

        // *** Set save path
        $args[0] = $this->file->path;

        if (!$this->_output($this->file->extension, $args)) {
            throw new AeImageException('Cannot save image: internal error', 500);
        }

        return $this;
    }

    /**
     * Display image
     *
     * Outputs an image to the stream, including the correct Content-Type
     * header. Invoking this method alone on an image will send the correct data
     * to the browser:
     * <code> $img = new AeImage('photo.jpg');
     * $img->display(); // Ouputs the image in jpeg format
     * die; // End the script to prevent unnecessary output</code>
     *
     * This method also accepts different parameters depending on the
     * <var>$type</var> value:
     * <code> $img = new AeImage('photo.jpg');
     * $img->display('jpg', 75); // See {@link php.net/imagejpeg imagejpeg()} function documentation
     * $img->display('png', 9, PNG_ALL_FILTERS); // See {@link php.net/imagepng imagepng()} function documentation</code>
     *
     * @throws AeImageException #400 if no type value passed and no file associated
     * @throws AeImageException #412 if headers have already been sent (output started)
     *
     * @uses AeImage::_output() to display the image
     *
     * @param AeString|string $type
     *
     * @return bool
     */
    public function display($type = null)
    {
        if ($type instanceof AeString) {
            $type = $type->getValue();
        }

        if (!is_string($type))
        {
            if (!is_object($this->file)) {
                throw new AeImageException('No type value passed', 400);
            }

            $type = $this->file->extension;
        }

        $args = func_get_args();

        array_shift($args);
        array_unshift($args, null);

        if (headers_sent()) {
            throw new AeImageException('Cannot display image: headers already sent', 412);
        }

        ob_start();

        if ($this->_output($type, $args)) {
            header('Content-Type: ' . $this->_getMime($type));
            ob_end_flush();
            return true;
        }

        ob_end_clean();

        return false;
    }

    /**
     * Get Mime type
     *
     * Returns an image's Mime type to be used with the Content-Type header
     *
     * @uses AeImage::_getMime()
     *
     * @return string|bool a valid mime type or false
     */
    public function getMime()
    {
        if (!is_object($this->file)) {
            return false;
        }

        return new AeString($this->_getMime($this->file->extension));
    }

    /**
     * Get specific Mime type
     *
     * Returns the Mime type for an image in the <var>$type</var> format
     *
     * @throws AeImageException #501 if driver is not an implementation of
     *                               the {@link AeInterface_Image} interface
     *
     * @param string $type
     *
     * @return string
     */
    protected function _getMime($type)
    {
        switch ($type)
        {
            case 'jpg':
            case 'jpe':
            case 'jpeg': {
                return 'image/jpeg';
            } break;

            case 'png': {
                return 'image/png';
            } break;

            case 'gif': {
                return 'image/gif';
            } break;

            case 'wbmp': {
                return 'image/vnd.wap.wbmp';
            } break;

            case 'gd2': {
                return 'image/gd2';
            } break;

            case 'gd': {
                return 'image/gd';
            } break;

            default:
            {
                $driver = 'AeImage_Driver_' . ucfirst(strtolower($type));

                if (class_exists($driver))
                {
                    $driver = new $driver;

                    if (!($driver instanceof AeInterface_Image)) {
                        throw new AeImageException(ucfirst($driver) . ' driver has an invalid access interface', 501);
                    }

                    return call_user_func(array($driver, 'getMime'));
                }

                return 'image/' . $type;
            } break;
        }
    }

    /**
     * Call output function
     *
     * Detects and calls the correct output function for the <var>$type</var>
     * image type
     *
     * @throws AeImageException #404 if driver not found
     * @throws AeImageException #501 if driver is not an implementation of
     *                               the {@link AeInterface_Image} interface
     *
     * @param string $type
     * @param array  $args
     *
     * @return bool
     */
    protected function _output($type, $args)
    {
        switch ($type)
        {
            case 'jpg':
            case 'jpe': {
                $type = 'jpeg';
            } // break intentionally left out

            default:
            {
                $driver = 'AeImage_Driver_' . ucfirst(strtolower($type));

                if (!class_exists($driver))
                {
                    $function = 'image' . $type;

                    if (!function_exists($function)) {
                        throw new AeImageException(ucfirst($driver) . ' driver not found', 404);
                    }
                } else {
                    $driver = new $driver;

                    if (!($driver instanceof AeInterface_Image)) {
                        throw new AeImageException(ucfirst($driver) . ' driver has an invalid access interface', 501);
                    }

                    $function = array($driver, 'output');
                }
            } break;
        }

        array_unshift($args, $this->getImage());

        if (!@call_user_func_array($function, $args)) {
            return false;
        }

        return true;
    }

    /**
     * Copy image
     *
     * Copies the image file to the specified destination file and returns a new
     * AeImage object instance.
     *
     * Returns false, if object is not associated with an actual image file
     *
     * @param AeString|string $path
     *
     * @return AeImage|bool
     */
    public function copy($path)
    {
        if ($path instanceof AeString) {
            $path = $path->getValue();
        }

        if (!is_object($this->_file)) {
            return false;
        }

        return new AeImage($this->file->copy($path)->path);
    }

    /**
     * Get image file
     *
     * Returns an image file object, wrapped in the {@link AeFile} class
     * instance (or one of its subdrivers). This is useful when working with
     * uploaded images:
     * <code> $image = new AeImage($_FILES['foo']);
     * $image->getFile()->move('images');</code>
     *
     * @see AeFile
     *
     * @return AeFile
     */
    public function getFile()
    {
        if ($this->_file === null) {
            return false;
        }

        if (is_string($this->_file)) {
            $this->setFile(AeFile::getInstance($this->_file));
        }

        return $this->_file;
    }

    /**
     * Get image resource
     *
     * Returns an image resource identifier, used by the internal PHP image
     * manipulation methods
     *
     * @throws AeImageException #404 if driver not found
     * @throws AeImageException #400 if no image resource or image file is
     *                               available
     * @throws AeImageException #501 if driver is not an implementation of
     *                               the {@link AeInterface_Image} interface
     *
     * @return resource
     */
    public function getImage()
    {
        if (!is_resource($this->_image))
        {
            if ($this->file === null) {
                throw new AeImageException('No image is available', 400);
            }

            $type = $this->file->extension;

            switch ($type)
            {
                case 'jpg':
                case 'jpe': {
                    $type = 'jpeg';
                } // break intentionally left out

                default:
                {
                    $driver = 'AeImage_Driver_' . ucfirst(strtolower($type));

                    if (!class_exists($driver))
                    {
                        $function = 'imagecreatefrom' . $type;

                        if (!function_exists($function)) {
                            throw new AeImageException(ucfirst($driver) . ' driver not found', 404);
                        }
                    } else {
                        $driver = new $driver;

                        if (!($driver instanceof AeInterface_Image)) {
                            throw new AeImageException(ucfirst($driver) . ' driver has an invalid access interface', 501);
                        }

                        $function = array($driver, 'getImage');
                    }
                } break;
            }

            $this->_image = call_user_func($function, $this->file->path);
        }

        return $this->_image;
    }
}

/**
 * Image exception class
 *
 * Image-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeImageException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Image');
        parent::__construct($message, $code);
    }
}
?>
