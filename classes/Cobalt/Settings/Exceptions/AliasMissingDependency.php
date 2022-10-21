<?php

namespace Cobalt\Settings\Exceptions;

use Exception;

class AliasMissingDependency extends Exception {
    var string $message;
    function __construct(string $message) {
        $this->message = $message;
    }
}