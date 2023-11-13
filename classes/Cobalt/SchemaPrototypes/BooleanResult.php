<?php

namespace Cobalt\SchemaPrototypes;

class BooleanResult extends SchemaResult {
    protected $type = "boolean";
    
    public function display():string {
        $valid = $this->valid();
        $val = $this->getValue();
        $str = ($val) ? "true" : "false";
        if(!empty($valid)) {
            if(key_exists($str, $valid)) return $valid[$str];
            if(key_exists($val, $valid)) return $valid[$val];
        }
        return $val;
    }

    public function __toString(): string {
        return ($this->getValue()) ? "true" : "false";
    }

}