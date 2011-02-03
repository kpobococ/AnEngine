<?php

abstract class AeCore
{
    private static $_prefixes = array();

    public static function load()
    {
        if (!defined('SLASH')) {
            define('SLASH', DIRECTORY_SEPARATOR);
        }

        set_include_path(realpath(dirname(__FILE__) . SLASH . '..') . PATH_SEPARATOR .
                         get_include_path());

        if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
            throw new Exception('AnEngine framework requires PHP version 5.2.0 or later', 505);
        }

        self::addClassPrefix('Ae', 'ae' . SLASH . 'classes');

        require_once 'ae' . SLASH . 'autoload.php';

        AeAutoload::extensions('.php');
        AeAutoload::register(array('AeCore', 'autoload'));
    }

    public static function autoload($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return true;
        }

        if (!($file = self::findClass($class))) {
            return false;
        }

        include_once $file;
        return true;
    }

    public static function addClassPrefix($prefix, $path = null)
    {
        if (is_array($prefix) || $prefix instanceof Traversable)
        {
            foreach ($prefix as $k => $v) {
                self::addClassPrefix($k, $v);
            }

            return;
        }

        $path = rtrim($path, '\\/');

        if ($path === null) {
            throw new Exception('No path specified', 400);
        }

        if (!file_exists($path)) {
            throw new Exception('Path does not exist: ' . $path, 404);
        }

        if (!is_dir($path)) {
            throw new Exception('Path is not a directory: ' . $path, 405);
        }

        if (isset(self::$_prefixes[$prefix])) {
            unset(self::$_prefixes[$prefix]);
        }

        self::$_prefixes[$prefix] = $path;
    }

    public static function removeClassPrefix($prefix)
    {
        if (is_array($prefix) || $prefix instanceof Traversable)
        {
            foreach ($prefix as $p) {
                self::removeClassPrefix($p);
            }

            return;
        }

        unset(self::$_prefixes[$prefix]);
    }

    public static function getPrefixPath($prefix)
    {
        if (!isset(self::$_prefixes[$prefix])) {
            return false;
        }

        return self::$_prefixes[$prefix];
    }

    public static function getPrefixes()
    {
        return self::$_prefixes;
    }

    public static function findClass($class)
    {
        $file = self::getClassPath($class);

        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path)
        {
            foreach (AeAutoload::extensions() as $ext)
            {
                if (file_exists(realpath($path) . SLASH . $file . $ext)) {
                    return $file . $ext;
                }
            }
        }

        return false;
    }

    public static function getClassPath($class)
    {
        $base = '';

        foreach (self::$_prefixes as $prefix => $path)
        {
            if (strpos($class, $prefix) === 0) {
                $base  = $path . SLASH;
                $class = substr($class, strlen($prefix));
                break;
            }
        }

        $bits = array();

        foreach (explode('_', $class) as $bit)
        {
            if (strlen($bit) == 0) {
                continue;
            }

            $bits[] = strtolower($bit[0]) . substr($bit, 1);
        }

        return $base . implode(SLASH, $bits);
    }
}