<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\Maps\GenericMap;

class MapResult extends SchemaResult {
    
    function filter($value) {
    return $this->value->validate($value);
    }

    function __isset($path) {
        return $this->value->__isset($path);
    }

    public function jsonSerialize(): mixed {
        return $this->originalValue;
    }
    // function setName(string $name) {
    //     // TODO: Set the appropriate name
    // }

    // function setSchema(?array $schema): void {
    //     $this->schema = array_merge(
    //         self::universalSchemaDirectives,
    //         $this->defaultSchemaValues(),
    //         $schema ?? []
    //     );
    //     // $this->value->schema;
    // }

    function setValue(mixed $value): void {
        $this->originalValue = $value;
        $this->value = new GenericMap($value, $this->schema ?? []);
    }

    function __getHydrated():array {
        return $this->value->__hydrated;
    }

}