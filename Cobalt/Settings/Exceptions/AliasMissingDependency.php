<?php

namespace Cobalt\Settings\Exceptions;

use Exception;

class AliasMissingDependency extends Exception {
    function __construct(string $message) {
        $this->message = $message;
    }
}