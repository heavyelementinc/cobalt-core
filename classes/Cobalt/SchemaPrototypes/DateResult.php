<?php

namespace Cobalt\SchemaPrototypes;

class SchemaDateResult extends SchemaResult {
    protected $type = "date";
    
    public function display():string {
        return "";
    }

    public function format(string $format):string {
        return $format;
    }

}