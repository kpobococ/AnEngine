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
     * Image dimensions
     * @var array
     */
    protected $_size = null;

    /**
     * Enlarge image on resize
     * @see AeImage::resize()
     */
    const RESIZE_ENLARGE = 1;

    /**
     * Reduce image on resize
     * @see AeImage::resize()
     */
    const RESIZE_REDUCE  = 2;

    /**
     * Save aspect ratio on resize
     * @see AeImage::resize()
     */
    const RESIZE_KEEP_RATIO = 4;

    /**
     * Resize width flag
     * @see AeImage::resize()
     */
    const RESIZE_WIDTH = 8;

    /**
     * Resize height flag
     * @see AeImage::resize()
     */
    const RESIZE_HEIGHT = 16;

    /**
     * Default resize value
     * @see AeImage::resize()
     */
    const RESIZE_DEFAULT = 31;

    /**
     * Fit smaller dimension on resize
     * @see AeImage::resize()
     */
    const RESIZE_FIT_SMALLER = 32;

    /**
     * Do not resample
     * @see AeImage::resize(), AeImage::crop()
     */
    const NO_RESAMPLE = 64;

    const CROP_LEFT = 1;
    const CROP_RIGHT = 2;
    const CROP_TOP = 4;
    const CROP_BOTTOM = 8;
    const CROP_CENTER = 15;

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

        if ($source !== null) {
            $this->load($source);
        }
    }

    public function load($source)
    {
        if ($source instanceof AeType) {
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

        return $this;
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
        if ($this->_size === null)
        {
            if (is_resource($this->_image))
            {
                $info = array(@imagesx($this->_image), @imagesy($this->_image));

                if (!is_int($info[0]) || !is_int($info[1])) {
                    $info = null;
                }
            } else {
                if ($this->file === null) {
                    throw new AeImageException('No image is available', 400);
                }

                $info = array_slice(@getimagesize($this->file->path), 0, 2);
            }

            if (!is_array($info))
            {
                // *** Try and load an image using external driver
                $image = $this->getImage();
                $info  = array(@imagesx($image), @imagesy($image));

                if (!is_int($info[0]) || !is_int($info[1])) {
                    $info = null;
                }
            }

            if (!is_array($info)) {
                throw new AeImageException('Could not detect image dimensions', 500);
            }

            $this->_size = array_combine(array('x', 'y'), $info);
        }

        return AeType::wrapReturn($this->_size);
    }

    /**
     * Resize image
     *
     * Resizes the image and returns a modified image object. Does not modify
     * the original, so you have to save the resized image manually. Requires
     * width, height or both, depending on the mode.
     *
     * Mode is a bitmask of available resize options. The available options are:
     *  - {@link AeImage::RESIZE_ENLARGE}: enlarge the image to the specified
     *                    dimensions. Enabled by default.
     *  - {@link AeImage::RESIZE_REDUCE}:  reduce the image to the specified
     *                    dimensions. Enabled by default.
     *  - {@link AeImage::RESIZE_KEEP_RATIO}: if this is enabled, image aspect
     *                    ratio is kept, adjusting width or height (depends on
     *                    other options). Enabled by default.
     *  - {@link AeImage::RESIZE_WIDTH}:  modify image width. Enabled by default.
     *  - {@link AeImage::RESIZE_HEIGHT}: modify image height. Enabled by default.
     *  - {@link AeImage::RESIZE_FIT_SMALLER}: if this is enabled, image
     *                    dimensions are resized so that smaller dimension fits
     *                    the requested value. This is useful when you want to
     *                    crop the resulting image to fit the dimensions
     *                    perfectly.
     *  - {@link AeImage::NO_RESAMPLE}: if this is enabled, image is not
     *                    resampled, only resized. This reduces resulting image
     *                    quality, but also reduces time required to perform the
     *                    resizing operation.
     *
     * <b>NOTE:</b> this method requires at least two flags to be set: one (or
     * both) of the {@link AeImage::RESIZE_ENLARGE} and {@link
     * AeImage::RESIZE_REDUCE} flags to define the resizing operation and one
     * (or both) of the {@link AeImage::RESIZE_WIDTH} and {@link
     * AeImage::RESIZE_HEIGHT} flags to define the resizing target dimension. If
     * none of these flags are set, an exception is thrown. The default value
     * has all four of these set.
     *
     * Assuming you want to generate a 200x100 thumbnail of your photo, keeping
     * the aspect ratio of the original, this is the code required:
     * <code> $image->resize(200, 100,
     *     AeImage::RESIZE_REDUCE + AeImage::RESIZE_KEEP_RATION
     *     + AeImage::RESIZE_WIDTH + AeImage::RESIZE_HEIGHT
     * );</code>
     *
     * This will ensure that images, smaller than 200x100, will not get resized.
     * But since the default value has all these options set (as the most
     * common ones), you can just exclude the only option you don't need:
     * <code> $thumb = $image->resize(200, 100, AeImage::RESIZE_DEFAULT ^ AeImage::RESIZE_ENLARGE);</code>
     *
     * If you want to only specify new image height and autodetect the new width
     * according to the aspect ratio of the original image, use null to skip the
     * width parameter:
     * <code> $thumb = $image->resize(null, 100);</code>
     *
     * @param int $width
     * @param int $height
     * @param int $mode
     *
     * @return AeImage resized image
     */
    public function resize($width, $height = null, $mode = self::RESIZE_DEFAULT)
    {
        if ($width instanceof AeScalar) {
            $width = $width->getValue();
        }

        if ($height instanceof AeScalar) {
            $height = $height->getValue();
        }

        if ($mode === null) {
            $mode = AeImage::RESIZE_DEFAULT;
        }

        // *** Detect flags
        $f_enlarge = ($mode & self::RESIZE_ENLARGE) === self::RESIZE_ENLARGE;
        $f_reduce  = ($mode & self::RESIZE_REDUCE)  === self::RESIZE_REDUCE;
        $f_width   = ($mode & self::RESIZE_WIDTH)   === self::RESIZE_WIDTH;
        $f_height  = ($mode & self::RESIZE_HEIGHT)  === self::RESIZE_HEIGHT;

        $f_keepRatio  = ($mode & self::RESIZE_KEEP_RATIO)  === self::RESIZE_KEEP_RATIO;
        $f_fitSmaller = ($mode & self::RESIZE_FIT_SMALLER) === self::RESIZE_FIT_SMALLER;
        $f_noResample = ($mode & self::NO_RESAMPLE)        === self::NO_RESAMPLE;

        if (!$f_enlarge && !$f_reduce) {
            throw new AeImageException('At least one of RESIZE_ENLARGE or RESIZE_REDUCE flags must be set', 400);
        }

        if (!$f_width && !$f_height) {
            throw new AeImageException('At least one of RESIZE_WIDTH or RESIZE_HEIGHT flags must be set', 400);
        }

        if ($width === null && $height === null) {
            throw new AeImageException('At least one dimension is required', 400);
        }

        $size = $this->getSize();

        if ($size instanceof AeArray) {
            $size = $size->getValue();
        }

        $x = $size['x'];
        $y = $size['y'];

        // *** Save original width, if not conf'd to resize width
        if ($width === null && !$f_width) {
            $width = $x;
        }

        // *** Save original height, if not conf'd to resize height
        if ($height === null && !$f_height) {
            $height = $y;
        }

        // *** Calculate dimensions if keeping original aspect ratio
        if ($f_keepRatio)
        {
            $ratio = $x / $y;

            if ($width && !$height) {
                $height = $width * $y / $x;
            } else if ($height && !$width) {
                $width  = $height * $x / $y;
            } else {
                $_ratio = $width / $height;

                if ($ratio != $_ratio)
                {
                    // *** Adjust resulting dimensions
                    if (($ratio < $_ratio && $f_fitSmaller) || ($ratio > $_ratio && !$f_fitSmaller)) {
                        $height = $width * $y / $x;
                    } else if (($f_fitSmaller && $ratio > $_ratio) || (!$f_fitSmaller && $ratio < $_ratio)) {
                        $width  = $height * $x / $y;
                    }
                }
            }
        }

        // *** Detect if we even require any operations
        if ($width == $x && $height == $y) {
            return $this;
        }

        if (!$f_enlarge)
        {
            // *** Ensure no enlargement is performed
            if ($width > $x) {
                $width = $x;
            }

            if ($height > $y) {
                $height = $y;
            }
        }

        if (!$f_reduce)
        {
            // *** Ensure no reduction is performed
            if ($width < $x) {
                $width = $x;
            }

            if ($height < $y) {
                $height = $y;
            }
        }

        // *** Resize the image
        // TODO: add support for image drivers
        $new      = imagecreatetruecolor($width, $height);
        $function = 'imagecopy';

        if ($f_noResample) {
            $function .= 'resized';
        } else {
            $function .= 'resampled';
        }

        if (!function_exists($function)) {
            throw new AeImageException('Resize mode unavailable on this system', 500);
        }

        if (!@call_user_func($function, $new, $this->image, 0, 0, 0, 0, $width, $height, $x, $y)) {
            throw new AeImageException('Image resize failed', 500);
        }

        $return = new AeImage;

        $return->setImage($new);

        return $return;
    }

    public function crop($width, $height, $mode = AeImage::CROP_CENTER)
    {
        if ($width instanceof AeScalar) {
            $width = $width->getValue();
        }

        if ($height instanceof AeScalar) {
            $height = $height->getValue();
        }

        if ($mode === null) {
            $mode = AeImage::RESIZE_DEFAULT;
        }

        $f_left   = ($mode & self::CROP_LEFT)   === self::CROP_LEFT;
        $f_right  = ($mode & self::CROP_RIGHT)  === self::CROP_RIGHT;
        $f_top    = ($mode & self::CROP_TOP)    === self::CROP_TOP;
        $f_bottom = ($mode & self::CROP_BOTTOM) === self::CROP_BOTTOM;

        $f_noResample = ($mode & self::NO_RESAMPLE) === self::NO_RESAMPLE;

        if (!$f_left && !$f_right) {
            throw new AeImageException('At least one of CROP_LEFT or CROP_RIGHT flags must be set', 400);
        }

        if (!$f_top && !$f_bottom) {
            throw new AeImageException('At least one of CROP_TOP or CROP_BOTTOM flags must be set', 400);
        }

        $size = $this->getSize();

        if ($size instanceof AeArray) {
            $size = $size->getValue();
        }

        $x = $size['x'];
        $y = $size['y'];

        // *** Detect if we even require any operations
        if ($width == $x && $height == $y) {
            return $this;
        }

        $srcX = 0;
        $srcY = 0;

        if ($width < $x && $f_right)
        {
            $srcX = $x - $width;

            if ($f_left) {
                // *** Center
                $srcX = $srcX / 2;
            }
        }

        if ($height < $y && $f_bottom)
        {
            $srcY = $y - $height;

            if ($f_top) {
                // *** Center
                $srcY = $srcY / 2;
            }
        }

        // *** Crop the image
        // TODO: add support for image drivers
        $new      = imagecreatetruecolor($width, $height);
        $function = 'imagecopy';

        if ($f_noResample) {
            $function .= 'resized';
        } else {
            $function .= 'resampled';
        }

        if (!function_exists($function)) {
            throw new AeImageException('Crop mode unavailable on this system', 500);
        }

        if (!@call_user_func($function, $new, $this->image, 0, 0, $srcX, $srcY, $width, $height, $width, $height)) {
            throw new AeImageException('Image crop failed', 500);
        }

        $return = new AeImage;

        $return->setImage($new);

        return $return;
    }

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
     * @return AeImage self
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

        try {
            $this->_output($type, $args);
            header('Content-Type: ' . $this->_getMime($type));
            ob_end_flush();
        } catch (AeImageException $e) {
            // *** Buffer control
            ob_end_clean();
            throw $e;
        }

        return $this;
    }

    /**
     * Get Mime type
     *
     * Returns an image's Mime type to be used with the Content-Type header
     *
     * @uses AeImage::_getMime()
     *
     * @return string
     */
    public function getMime()
    {
        if (!is_object($this->file)) {
            throw new AeImageException('No path value passed', 400);
        }

        return AeType::wrapReturn($this->_getMime($this->file->extension));
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
        @call_user_func_array($function, $args);

        return $this;
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
            throw new AeImageException('No path value passed', 400);
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
            throw new AeImageException('No path value passed', 400);
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
