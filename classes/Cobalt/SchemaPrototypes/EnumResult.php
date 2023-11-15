<?php

namespace Cobalt\SchemaPrototypes;

use Validation\Exceptions\ValidationIssue;

/**
 * Custom schema entries:
 * 'strict' - @bool Determines if the filter allows values not found in the enum
 * @package Cobalt\SchemaPrototypes
 */

class EnumResult extends SchemaResult {
    protected $type = "string";
    public function display():string {
        $enum = $this->schema['valid'];
        $val = $this->getValue();
        if(in_array($val, $enum)) return $enum[$val];
        if(in_array($this->value, $enum)) return $enum[$this->value];
        return (string)$val;
    }

    function filter($value) {
        $enum = $this->schema['valid'];
        $val = $value;
        if(in_array($val, $enum)) return $enum[$val];
        $message = "Invalid selection";
        if(!in_array('strict', $this->schema)) throw new ValidationIssue($message);
        if($this->schema['strict'] === false) throw new ValidationIssue($message);
        return $value;
    }
}