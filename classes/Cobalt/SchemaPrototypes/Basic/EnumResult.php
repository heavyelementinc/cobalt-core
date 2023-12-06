<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Validation\Exceptions\ValidationIssue;

/**
 * Custom schema entries:
 * 'strict' - @bool Determines if the filter allows values not found in the enum
 * @package Cobalt\SchemaPrototypes
 */

class EnumResult extends SchemaResult {
    protected $type = "string";
    public function display():string {
        if(is_callable($this->schema['display'])) return $this->schema['display']($this->getValue(), $this, $this->getValid());
        $enum = $this->getValid();
        $val = $this->getValue();
        if(in_array($val, $enum)) return $enum[$val];
        if(in_array($this->value, $enum)) return $enum[$this->value];
        return (string)$val;
    }

    public function defaultSchemaValues(array $values = []):array {
        return [
            'strict' => true
        ];
    }

    function filter($value) {
        $enum = $this->getValid();
        $val = $value;
        if(key_exists($val, $enum)) return $val;
        $message = "Invalid selection";
        $strict = $this->schema['strict'] || true;
        if($strict) throw new ValidationIssue($message);
        return $value;
    }
}