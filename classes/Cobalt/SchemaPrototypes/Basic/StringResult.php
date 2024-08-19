<?php

namespace Cobalt\SchemaPrototypes\Basic;

use ArrayAccess;
use Cobalt\SchemaPrototypes\SchemaResult;
// use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

/**
 * ## Schema directives
 * 'max'           - <int> The max length of the value. If it's not specified, the string can be any length.
 * 'min'           - <int> The minumum length of the value
 * `illegal_chars` - <string> A string of characters that must not be included in the subject
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
    protected function toUpper() {
        return $this->uppercase();
    }

    #[Prototype]
    protected function lowercase() {
        return strtolower($this->value);
    }

    #[Prototype]
    protected function toLowercase() {
        return $this->lowercase();
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
    protected function resverse() {
        return strrev($this->value);
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
        $length = strlen($this->getValue());
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
        // Get our subject's length
        $length = strlen($value);
        $min = $this->getDirective("min");
        if(is_null($min)) $min = 0;
        if($length < $min) throw new ValidationIssue("This value must be at least $min characters");
        
        $max = $this->getDirective("max");
        if(is_null($max)) return $value;
        if($length <= $max) return $value;
        throw new ValidationIssue("This value may not be greater than $max characters");
    }
    
    function illegal_chars($value) {
        if(!key_exists('illegal_chars', $this->schema)) return $value;
        // Split our string of illegal characters into an array
        $illegal = str_split($this->getDirective('illegal_chars'));
        // Remove any illegal characters from the subject
        $mutant = str_replace($illegal, "", $value);
        // Compare the subject and the mutated value. If they don't match then
        // the subject must have contained illegal characters
        if($mutant !== $value) throw new ValidationIssue("This entry contains illegal characters.");
        return $value;
    }
    
    function filter($value) {
        $this->character_limit($value);
        $this->illegal_chars($value);
        return $value;
    }
}