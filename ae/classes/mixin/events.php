<?php

class AeMixin_Events extends AeMixin
{
    private $___events = array();

    public function addEvent($name, $callback)
    {
        $type = AeType::of($name);

        if ($type != 'string') {
            throw new InvalidArgumentException('Expecting name to be string, ' . $type . ' given', 400);
        }

        $name     = strtolower((string) $name);
        $listener = $callback instanceof AeCallback ? $callback : new AeCallback($callback);

        if (!isset($this->___events[$name]) || !is_array($this->___events[$name])) {
            $this->___events[$name] = array();
        }

        $this->___events[$name][] = $listener;

        return $listener;
    }

    public function removeEvent($name, AeCallback $listener)
    {
        $type = AeType::of($name);

        if ($type != 'string') {
            throw new InvalidArgumentException('Expecting name to be string, ' . $type . ' given', 400);
        }

        $name = strtolower((string) $name);

        if (isset($this->___events[$name]) && is_array($this->___events[$name]))
        {
            $events = $this->___events[$name];

            foreach ($events as $i => $l)
            {
                if ($listener === $l) {
                    unset($events[$i]);
                    $this->___events[$name] = array_values($events);
                    break;
                }
            }
        }

        return $this->_getOwner();
    }

    public function addEvents($events)
    {
        $type = AeType::of($events);

        if ($type != 'array') {
            throw new InvalidArgumentException('Expecting array, ' . $type . ' given', 400);
        }

        $return = array();

        foreach ($events as $name => $callback) {
            $return[$name] = $this->addEvent($name, $callback);
        }

        return $return;
    }

    public function removeEvents($events)
    {
        $type = AeType::of($events);

        if ($type != 'array' && $type != 'string') {
            throw new InvalidArgumentException('Expecting array, ' . $type . ' given', 400);
        }

        if ($type == 'string') {
            $name = strtolower((string) $events);

            unset($this->___events[$name]);
        } else {
            foreach ($events as $name => $callback) {
                $this->removeEvent($name, $callback);
            }
        }

        return $this->_getOwner();
    }

    public function fireEvent($name, $args = null)
    {
        $type = AeType::of($name);

        if ($type != 'string') {
            throw new InvalidArgumentException('Expecting name to be string, ' . $type . ' given', 400);
        }

        $name   = strtolower((string) $name);
        $return = true;
        $args   = $args !== null && !is_array($args) ? array($args) : $args;

        if (isset($this->___events[$name]) && is_array($this->___events[$name]))
        {
            foreach ($this->___events[$name] as $callback) {
                $return = $return && ($callback->call($args) !== false);
            }
        }

        return $return;
    }

    public function cloneEvents(AeObject $object, $name = null)
    {
        $mixins = $object->getMixins();
        $source = null;

        foreach ($mixins as $mixin)
        {
            if ($mixin instanceof AeMixin_Events) {
                $source = $mixin;
                break;
            }
        }

        if ($name !== null)
        {
            $name = strtolower((string) $name);

            if (isset($source->___events[$name]) && is_array($source->___events[$name]))
            {
                foreach ($source->___events[$name] as $listener) {
                    $this->addEvent($name, clone $listener);
                }
            }
        } else {
            foreach ($source->___events as $name => $events) {
                $this->cloneEvents($object, $name);
            }
        }

        return $this->_getOwner();
    }
}