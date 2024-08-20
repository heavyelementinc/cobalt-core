<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

/**
 * Custom schema entries:
 * 'strict' - @bool Determines if the filter allows values not found in the enum
 * 'allow_custom' - @bool Allows the field to Overrides 'strict'
 * @package Cobalt\SchemaPrototypes
 */

class EnumResult extends SchemaResult {
    use Fieldable;

    protected $type = "string";

    function defaultSchemaValues(array $data = []): array {
        return [
            'strict' => true,
            'allow_custom' => false,
        ];
    }

    function __defaultIndexPresentation(): string {
        return $this->display();
    }
    
    function filter($value) {
        $enum = $this->getValid();
        
        // If the value exists as a key in the array, continue;
        $val = $value;
        if(key_exists($val, $enum)) return $val;

        // Check if `strict` is set as a schema element
        $strict = $this->isStrict();

        $message = "Invalid selection";
        // $strict = $this->schema['strict'] ?? true;
        if($strict) throw new ValidationIssue($message);
        return $value;
    }

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

}