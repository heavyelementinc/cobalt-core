<?php

namespace Cobalt\SchemaPrototypes\Basic;

use ArrayAccess;
use Cobalt\SchemaPrototypes\SchemaResult;
// use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

/**
 * Custom schema entries:
 * 'max' - @int The max length of the value. If it's not specified, the string can be any length.
 * @package Cobalt\SchemaPrototypes
 */
class StringResult extends SchemaResult implements ArrayAccess{
    // use Fieldable;
    protected $type = "string";

    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    
    #[Prototype]
    protected function length():int|null {
        return strlen($this->value);
    }

    #[Prototype]
    protected function capitalize(){
        return ucfirst($this->value);
    }

    #[Prototype]
    protected function uppercase() {
        return strtoupper($this->value);
    }

    #[Prototype]
    protected function toUppercase() {
        return $this->uppercase();
    }

    #[Prototype]
    protected function lowercase() {
        return strtolower($this->value);
    }

    #[Prototype]
    protected function toLower() {
        return $this->lowercase();
    }

    #[Prototype]
    protected function last() {
        return $this->value[count($this->value) - 1];
    }

    #[Prototype]
    protected function field():string {
        return "<input type=\"Text\" name=\"$this->name\" value=\"".$this->getValue()."\">";
    }

    #[Prototype]
    protected function substring(string $start, ?string $length = null, array $options = []) {
        return substr($this->getValue(), $start, $length);
    }

    #[Prototype]
    protected function display():string {
        $val = $this->getValue();
        $valid = $this->getValid();
        
        // Since 'display' is already a method, we need to manually invoke the 
        // `display` directive if it exists.
        $directive = $this->getDirective("display");
        if(is_callable($directive)) return $directive($val, $this->name, $valid);

        if(is_array($valid)) {
            if(key_exists($val, $valid)) return $valid[$val];
            if(key_exists($this->value, $valid)) return $valid[$this->value];
        }
        return (string)$val;
    }

    public function offsetExists(mixed $offset): bool {
        $length = count($this->getValue());
        if($offset < 0) return false;
        if($length >= $offset) return false;
        return true;
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->getValue()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        return;
    }

    public function offsetUnset(mixed $offset): void {
        return;
    }

    function character_limit($value) {
        if(!key_exists('max', $this->schema)) return $value;
        $length = strlen($value);
        $max = $this->schema['max'];
        if($length <= $max) return $value;
        throw new ValidationIssue("This may not be greater than $max characters long");
    }
    
    function restricted_chars($value) {
        if(!key_exists('illegal_chars', $this->schema)) return $value;
        $illegal = str_split($this->schema['illegal_chars']);
        $mutant = str_replace($illegal, "", $value);
        if($mutant !== $value) throw new ValidationIssue("This entry contains illegal characters.");
        return $value;
    }
    
    function filter($value) {
        $this->character_limit($value);
        $this->restricted_chars($value);
        return $value;
    }
}