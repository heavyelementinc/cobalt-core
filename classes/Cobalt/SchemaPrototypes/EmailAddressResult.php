<?php

namespace Cobalt\SchemaPrototypes;

use Validation\Exceptions\ValidationIssue;

class EmailAddressResult extends StringResult {
    protected $type = "string";

    function filter($value) {
        if(\filter_var($value, FILTER_VALIDATE_EMAIL)) return $value;
        throw new ValidationIssue("This value does not conform to known email formats");
    }
}