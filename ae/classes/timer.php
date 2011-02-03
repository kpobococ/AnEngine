<?php

class AeTimer extends AeObject
{
    protected $_start;

    public function __construct($start = null)
    {
        if ($start === null) {
            $start = self::microtime();
        }

        $this->setStart($start);
    }

    public function setStart($start)
    {
        if (!is_float($start)) {
            throw new InvalidArgumentException('Expecting float, ' . AeType::of($start) . ' given', 400);
        }

        $this->_start = $start;

        return $this;
    }

    public function toString($decimals = 6)
    {
        return number_format(self::microtime() - $this->getStart(), (int) $decimals, '.', '');
    }

    public static function microtime()
    {
        return microtime(true);
    }
}