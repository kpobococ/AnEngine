<?php

abstract class AeDebug
{
    protected static $_timer;

    public static function obCallback($output)
    {
        $timer = self::$_timer;

        if (!($timer instanceof AeTimer)) {
            return $output;
        }

        $header = 'PHP version: ' . PHP_VERSION;
        $footer = 'Finished execution in ' . $timer . ' seconds';
        $hlen   = strlen($header);
        $flen   = strlen($footer);
        $blen   = $hlen > $flen ? $hlen : $flen;
        $border = str_repeat('-', $blen);

        // Output
        header('Content-Type: text/plain; charset=UTF-8');
        return $header . "\n" . $border . "\n\n" .
               $output . "\n\n" .
               $border . "\n" . $footer;
    }

    public static function start($microtime = null)
    {
        self::$_timer = new AeTimer($microtime);
    }
}

AeDebug::start();

ob_start(array('AeDebug', 'obCallback'));