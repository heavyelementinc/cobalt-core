<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Validation\Exceptions\ValidationIssue;

class FakeResult extends SchemaResult {
    function filter($val) {
        throw new ValidationIssue("Field \"$this->name\" cannot be assigned a value.");
    }
}