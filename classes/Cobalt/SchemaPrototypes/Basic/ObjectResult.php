<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;

class ObjectResult extends SchemaResult {
    protected $type = "object";

    function __construct() {
        
    }

    public function __toString():string {
        if($this->asHTML) return "";
        return $this->display();
    }
}