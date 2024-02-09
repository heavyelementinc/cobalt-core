<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

/**
 * Custom schema entries:
 * 'strict' - @bool Determines if the filter allows values not found in the enum
 * @package Cobalt\SchemaPrototypes
 */

class EnumResult extends SchemaResult {
    use Fieldable;

    protected $type = "string";

    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    
    #[Prototype]
    protected function field($type = "select", $misc = []) {
        return $this->select($misc['class'] ?? "", $misc);
    }

    #[Prototype]
    protected function display():string {
        $enum = $this->getValid();
        $val = $this->getValue();
        if(key_exists($val, $enum)) return $enum[$val];
        if(key_exists($this->value, $enum)) return $enum[$this->value];
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