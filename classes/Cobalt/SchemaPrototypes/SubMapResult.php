<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\PersistanceMap;

class SubMapResult extends SchemaResult {
    
    protected PersistanceMap $__map;

    function __construct(PersistanceMap $map) {
        $this->__map = $map;
    }

    function filter($value) {
        return $this->__map->validate($value);
    }
}