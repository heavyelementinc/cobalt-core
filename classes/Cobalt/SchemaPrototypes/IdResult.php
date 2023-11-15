<?php

namespace Cobalt\SchemaPrototypes;

class IdResult extends SchemaResult {
    protected $type = "_id";
    
    public function display(): string {
        return (string)$this->value;
    }

    public function format(): string {
        return (string)$this->value;
    }
}