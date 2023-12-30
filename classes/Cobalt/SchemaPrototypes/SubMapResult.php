<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\PersistanceMap;
use Cobalt\SubMap;

class SubMapResult extends SchemaResult {
    
    function filter($value) {
        return $this->value->validate($value);
    }

    // function setName(string $name) {
    //     // TODO: Set the appropriate name
    // }

    function setValue(mixed $value): void {
        $this->originalValue = $value;
        $this->value = new SubMap($value, $this->schema->schema ?? []);
    }
}