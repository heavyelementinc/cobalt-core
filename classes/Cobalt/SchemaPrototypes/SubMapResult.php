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

    function setSchema(?array $schema): void {
        $this->schema = array_merge(
            self::universalSchemaDirectives,
            $this->defaultSchemaValues(),
            $schema ?? []
        );
        $this->value->schema
    }

    function setValue(mixed $value): void {
        $this->originalValue = $value;
        $this->value = new SubMap(null, $this->schema['schema'] ?? []);
        $this->value->ingest($value);
    }

}