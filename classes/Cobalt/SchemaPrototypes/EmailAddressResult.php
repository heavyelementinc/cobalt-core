<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\SchemaPrototypes\Traits\EmailValidation;
use Validation\Exceptions\ValidationIssue;

class EmailAddressResult extends StringResult {
    use EmailValidation;
    protected $type = "string";

    function filter($value) {
        $mutant = $this->email_validate($value);
        return $mutant;
    }
}