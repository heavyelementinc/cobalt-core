<?php

namespace Cobalt\SchemaPrototypes;

class EnumResult extends SchemaResult {
    protected $type = "string";
    public function display():string {
        $enum = $this->schema['valid'];
        $val = $this->getValue();
        if(in_array($val, $enum)) return $enum[$val];
        if(in_array($this->value, $enum)) return $enum[$this->value];
        return (string)$val;
    }
}