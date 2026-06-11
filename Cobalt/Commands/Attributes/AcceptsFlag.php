<?php

namespace Cobalt\Commands\Attributes;
use \Attribute;

#[Attribute]
class AcceptsFlags {
    public array $accepts = [];
    public function __construct(){
        $this->accepts = func_get_args();
    }
}