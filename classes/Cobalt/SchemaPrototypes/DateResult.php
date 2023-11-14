<?php

namespace Cobalt\SchemaPrototypes;

class DateResult extends SchemaResult {
    protected $type = "date";
    
    public function display():string {
        return "";
    }

    public function format(string $format):string {
        return $format;
    }

}