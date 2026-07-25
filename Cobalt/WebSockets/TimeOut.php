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

    function hasExpired(?int $time = null):bool {
        return $this->getDelta($time) >= $this->milliseconds;
    }

    function getDelta(?int $time = null):int {
        $time = $time ?? millitime();
        return $time - $this->start;
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