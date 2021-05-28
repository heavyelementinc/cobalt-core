<?php

namespace CRUD\Exceptions;

class ValidationIssue extends \Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}
