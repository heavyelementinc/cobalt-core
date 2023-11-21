<?php

namespace Cobalt\SchemaPrototypes\Traits;

use Validation\Exceptions\ValidationIssue;

trait EmailValidation {
    public function email_validate($value) {
        if(\filter_var($value, FILTER_VALIDATE_EMAIL)) return $value;
        throw new ValidationIssue("This value does not conform to known email formats");
    }
}