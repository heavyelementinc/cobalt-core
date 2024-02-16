<?php

namespace Cobalt\SchemaPrototypes\Traits;

use Cobalt\Maps\GenericMap;
use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;
use MongoDB\BSON\ObjectId;

trait ResultTranslator {
    function __toResult(string $name, mixed $value, ?array $schema, ?GenericMap $ref = null):SchemaResult|ObjectId {
        $type = gettype($value);

        switch($type) {
            case $value instanceof GenericMap:
                $result = $schema['type'] ?? new MapResult($value);
                break;
            case (isset($schema['type'])
                && $schema['type'] instanceof SchemaResult
            ):
                $result = $schema['type'];
                break;
            case "string":
                $result = new StringResult();
                break;
            case "integer":
            case "number":
                $result = new NumberResult();
                break;
            case "array":
                $result = new ArrayResult();
                break;
            case "boolean":
                $result = new BooleanResult();
                break;
            case "object":
                switch(get_class($value)) {
                    case "\\MongoDB\\BSON\\Array":
                        $result = new ArrayResult();
                        break;
                    case "\\MongoDB\\BSON\\UTCDateTime":
                        $result = new DateResult();
                        break;
                    case "\\MongoDB\\BSON\\ObjectId":
                        $result = new IdResult();
                        break;
                    case "\\Cobalt\\Maps\\GenericMap":
                        return new MapResult($value);
                }
            default:
                $result = new SchemaResult();
                break;
        }

        $result->setName($name);
        $result->setSchema($schema);
        
        if(!$ref) $ref = $this;
        $result->datasetReference($ref);

        $result->setValue($value);

        return $result;
    }

    private function __each(?Iterable $elements, GenericMap $ref, $startingIndex = 0) {
        if(!$elements) return [];
        if(!isset($this->schema['each'])) return $elements;
        if($this->schema['each'] instanceof SchemaResult) {
            $schema = $this->schema;
            $schema['type'] = $schema['each'];
            unset($schema['each']);
            $mutant = [];
            foreach($elements as $i => $v) {
                $mutant[$i] = $this->__toResult($this->name.'['.$startingIndex + $i."]", $v, $schema, $ref);
            }
            return $mutant;
        }
        return $elements;
    }
}