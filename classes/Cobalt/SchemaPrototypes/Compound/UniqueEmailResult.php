<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Traits\EmailValidation;
use Cobalt\SchemaPrototypes\Traits\UniqueValidation;
use Drivers\Database;
use Validation\Exceptions\ValidationIssue;

class UniqueEmailResult extends UniqueResult {
    use EmailValidation, UniqueValidation;

    function filter($value) {
        $mutant = $this->email_validate($value);
        if(!$this->isUnique($value)) throw new ValidationIssue("This email address is not unique");
        return $mutant;
    }
}