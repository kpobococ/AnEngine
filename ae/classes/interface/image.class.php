<?php
/**
 * Image interface file
 *
 * See {@link AeInterface_Image} interface documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */

/**
 * Image interface
 *
 * This is a common image driver interface. All image drivers must implement it.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */
interface AeInterface_Image
{
    /**
     * Output image
     *
     * Outputs an image to the browser or, if the optional <var>$path</var>
     * parameter is present, saves it to a file
     *
     * @param resource $image image gd resource
     * @param string   $path  image path
     *
     * @return bool true on success, false otherwise
     */
    public function output($image, $path = null);

    /**
     * Get image
     *
     * Creates an image resource from an image file
     *
     * @param string $path image file path
     *
     * @return resource
     */
    public function getImage($path);

    /**
     * Get image mime type
     *
     * Returns an image mime type to be used with the Content-Type header, like
     * image/jpeg
     *
     * @return string
     */
    public function getMime();
}

?>