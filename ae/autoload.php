<?php

abstract class AeAutoload
{
    public static function register($callback)
    {
        if ($callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        return spl_autoload_register($callback);
    }

    public static function unregister($callback)
    {
        if ($callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        return spl_autoload_unregister($callback);
    }

    public static function unshift($callback)
    {
        if ($callback instanceof AeCallback) {
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

    public static function shift()
    {
        $stack  = self::get();
        $return = array_shift($stack);

        if ($return !== null) {
            self::unregister($return);
        }

        return $return;
    }

    public static function push($callback)
    {
        if ($callback instanceof AeCallback) {
            $callback = $callback->getValue();
        }

        self::unregister($callback);
        self::register($callback);

        return count(self::get());
    }

    public static function pop()
    {
        $stack  = self::get();
        $return = array_pop($stack);

        if ($return !== null) {
            self::unregister($return);
        }

        return $return;
    }

    public static function get()
    {
        $return = spl_autoload_functions();

        return is_array($return) ? $return : array();
    }

    public static function call($class)
    {
        if (!class_exists($class, false)) {
            spl_autoload_call($class);
        }

        return class_exists($class, false);
    }

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
}