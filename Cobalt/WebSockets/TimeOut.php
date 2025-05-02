<?php

namespace Cobalt\WebSockets;

class TimeOut {
    private $callback;
    private int $milliseconds;
    private int $start;

    function getMilliseconds():int {
        return $this->milliseconds;
    }

    function setMilliseconds(int $value) {
        $this->milliseconds = $value;
    }

    function hasExpired($time = null):bool {
        $time = $time ?? floor(microtime(true) * 1000);
        return ($time - $this->start) >= $this->milliseconds;
    }

    function getCallback():callable {
        return $this->callback;
    }

    function setCallback($callback) {
        $this->callback = $callback;
    }

    function getStart():int {
        return $this->start;
    }

    function setStart(int $time) {
        $this->start = $time;
    }
}