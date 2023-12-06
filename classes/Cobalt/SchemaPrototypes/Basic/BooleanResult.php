<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Validation\Exceptions\ValidationIssue;

class BooleanResult extends SchemaResult {
    protected $type = "boolean";
    
    public function display():string {
        $valid = $this->getValid();
        $val = $this->getValue();
        $str = ($val) ? "true" : "false";
        if(!empty($valid)) {
            if(key_exists($str, $valid)) return $valid[$str];
            if(key_exists($val, $valid)) return $valid[$val];
        }
        return $str;
    }

    public function __toString(): string {
        return ($this->getValue()) ? "true" : "false";
    }

    function filter($value) {
        if(is_bool($value)) return $value;
        if(\filter_var($value, FILTER_VALIDATE_BOOL)) {
            return (in_array($value, [1, '1', 'true', 'on', 'yes'])) ? true : false;
        }
        throw new ValidationIssue("This value is not a boolean value, nor can it be unambiguously converted to a boolean value.");
    }
}