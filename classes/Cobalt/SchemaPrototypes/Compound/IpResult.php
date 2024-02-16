<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Basic\StringResult;
use Validation\Exceptions\ValidationIssue;

class IpResult extends StringResult {
    protected $type = "string";

    function filter($value) {
        if($value === null && $this->schema['nullable']) return null;
        if(\filter_var($value, FILTER_FLAG_IPV4)) return $value;
        throw new ValidationIssue("Data validation error"); // We're being ambiguous on purpose, here.
    }
}