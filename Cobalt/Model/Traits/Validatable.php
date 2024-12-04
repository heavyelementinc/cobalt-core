<?php

namespace Cobalt\Model\Traits;

trait Validatable {
    function __validate($value) {
        if(!$this->hasDirective('valid')) return $value;
        
    }

    function __options($value):string {

    }
}