<?php

namespace Cobalt\Commands\Exceptions;

use Error;
use Throwable;
use Override;

class CommandError extends Error {
    private int $errNo = 1;
    private string $color = "e";
    
    function __construct(string $message = "", int $code = 1, string $color = "e") {
        $this->errNo = $code;
        $this->color = $color;
        return parent::__construct($message);
    }

    function getErrNo() {
        return $this->errNo;
    }

    function getColor():string {
        return $this->color;
    }
}