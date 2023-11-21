<?php

namespace Cobalt\SchemaPrototypes;

class ObjectResult extends SchemaResult {
    protected $type = "object";

    function __construct() {
        
    }

    public function __toString():string {
        if($this->asHTML) return "";
        return $this->display();
    }
}