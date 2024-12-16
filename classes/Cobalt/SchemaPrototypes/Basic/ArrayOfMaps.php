<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\Maps\Exceptions\DirectiveException;
use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Traversable;
use TypeError;

/**
 * @property array 'each'
 * @package Cobalt\SchemaPrototypes\Compound
 */
class ArrayOfMaps extends SchemaResult {
    function setValue($value): void
    {
        if(!is_array($value) && $value instanceof Traversable === false) throw new TypeError("Value is not iterable");
        $each = $this->getDirective("each");
        if($each === null) throw new DirectiveException("ArrayOfMaps must define 'each' directive");

        $this->originalValue = $value;
        $this->value = [];
        foreach($value as $obj) {
            $map = new MapResult;
            $this->value[] = $map;
        }

        return;
    }

    function defaultSchemaValues(array $data = []): array
    {
        return [
            'each' => null
        ];
    }
}