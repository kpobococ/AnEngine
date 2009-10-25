<?php
/**
 * Files interface file
 *
 * See {@link AeInterface_File} interface documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */

/**
 * Files interface
 *
 * This is a common files driver interface. All files drivers must implement it.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 */
interface AeInterface_File extends Iterator
{
    public static function getInstance($filepath);
    public function load($filepath = null);
    public function isReadable();
    public function isWritable();
    public function isExecutable();
    public function isFile();
    public function isDirectory();
    public function isLink();
    public function isDot();
    public function isEmpty();
    public function getATime();
    public function getMTime();
    public function getCTime();
    public function getName();
    public function getPath();
    public function getMode();
    public function getSize($human = false);
    public function getType();
    public function getOwner($human = false);
    public function getGroup($human = false);
    public function getINode();
    public function getParent($object = true);
    public function touch($time = null);
    public function rename($name);
    public function move($path);
    public function setMode($mode);
    public function exists();
    public function delete();
    public function create();
}
?>