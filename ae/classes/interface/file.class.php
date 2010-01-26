<?php
/**
 * File interface file
 *
 * See {@link AeInterface_File} interface documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */

/**
 * File interface
 *
 * This is a common file interface. All file classes must implement it.
 *
 * @todo consider adding the copy() method
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */
interface AeInterface_File
{
    public function __construct($path = null);

    public function setPath($path);
    public function setMode($mode);

    public function isReadable();
    public function isWritable();
    public function isExecutable();

    public function isLink();
    public function isFile();
    public function isDirectory();

    public function getAccessTime();
    public function getModifiedTime();

    public function getName();
    public function getPath();
    public function getMode($octal = true);
    public function getSize($human = false);
    public function getType();

    public function getOwner($human = false);
    public function getGroup($human = false);
    public function getParent();

    public function touch($time = null);
    public function rename($name);
    public function move($path);
    public function copy($path);
    public function delete();
    public function create($mode = null);
    public function exists();
}
?>