<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;

class HrefResult extends SchemaResult {
    use Fieldable;
    protected $type = "href";
    
    public function filter($value) {
        if($this->getDirective('nullish') && !$value) return null;
        $partial = $this->schema['partial'] ?? false;
        if($partial) return $value;
        if(!filter_var($value, FILTER_VALIDATE_URL)) throw new ValidationIssue("This does not appear to be a valid URL");
        return $value;
    }
}