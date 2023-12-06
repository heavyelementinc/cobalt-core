<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\SchemaResult;
use Validation\Exceptions\ValidationIssue;

class HrefResult extends SchemaResult {
    protected $type = "href";
    
    public function filter($value) {
        $partial = $this->schema['partial'] ?? false;
        if($partial) return $value;
        if(!filter_var($value, FILTER_VALIDATE_URL)) throw new ValidationIssue("This does not appear to be a valid URL");
        return $value;
    }
}