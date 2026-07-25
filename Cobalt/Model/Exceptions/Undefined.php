<?php

namespace Cobalt\Model\Exceptions;

use Error;

class Undefined extends Error {
    function __construct(private string $name, $message) {
        parent::__construct($message);
    }

    function __toString(): string {
        return (config()['mode'] === COBALT_MODE_DEVELOPMENT) ? "<!-- ".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}.": undefined -->" : "";
    }
}