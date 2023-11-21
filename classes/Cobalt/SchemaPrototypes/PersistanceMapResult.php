<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\PersistanceMap;

class PersistanceMapResult extends PersistableResult {
    
    protected PersistanceMap $__map;

    function __construct(PersistanceMap $map) {
        $this->__map = $map;
    }

    function filter($value) {
        return $this->__map->validate($value);
    }
}