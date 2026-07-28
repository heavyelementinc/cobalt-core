<?php

namespace Cobalt\Exceptions;

use Exception;
use Throwable;
use Override;

class CobaltAutoloadFailure extends Exception {
    public function __construct(private string $className, string $message = "", int $code = 0, Throwable|null $previous = null){
        return parent::__construct($message, $code, $previous);
    }

    public function getClassName() {
        return $this->className;
    }
}