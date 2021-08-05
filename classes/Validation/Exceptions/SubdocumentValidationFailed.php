<?php

namespace Validation\Exceptions;

class SubdocumentValidationFailed extends ValidationFailed {
    function __construct($issues) {
        parent::__construct("Subdocument failed validation", $issues);
    }
}
