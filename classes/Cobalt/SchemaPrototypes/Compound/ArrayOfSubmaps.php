<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\Maps\Exceptions\DirectiveException;
use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Traversable;
use TypeError;

/**
 * @property array 'each'
 * @package Cobalt\SchemaPrototypes\Compound
 */
class ArrayOfSubmaps extends ArrayResult {
    function setValue($value): void
    {
        if(!is_array($value) && $value instanceof Traversable === false) throw new TypeError("Value is not iterable");
        $each = $this->getDirective("each");
        $instance = $this->getDirective('instance');
        if($each === null && $instance === null) throw new DirectiveException("ArrayOfSubmaps must define 'each' directive when no 'instance' directive is defined");
        if(!$instance) $instance = '\\Cobalt\\Maps\\GenericMap';

        $this->originalValue = $value;
        $this->value = [];
        foreach($value as $obj) {
            $this->value[] = new $instance($obj, $each);
        }

        return;
    }

    function defaultSchemaValues(array $data = []): array
    {
        return [
            'instance' => null,
            'each' => null
        ];
    }
}