<?php

namespace Cobalt\CLI;

class CLI {
    protected $command;
    protected $arguments = [];
    
    function __construct($command) {
        $args = func_get_args();
        array_shift($args);
        $this->command = $command;
        $this->arguments = $args;
    }

    
}
