<?php

namespace Cobalt\SchemaPrototypes;

class ObjectResult extends SchemaResult {
    protected $type = "object";

    public function __toString():string {
        if($this->asHTML) return "";
        return $this->display();
    }
}