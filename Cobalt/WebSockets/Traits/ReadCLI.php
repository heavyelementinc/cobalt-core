<?php

namespace Cobalt\WebSockets\Traits;

trait ReadCLI {
    private $stream;

    public function initialize() {
        $this->stream = fopen(STDIN, "r");
        stream_set_blocking($this->stream, false);
    }
}