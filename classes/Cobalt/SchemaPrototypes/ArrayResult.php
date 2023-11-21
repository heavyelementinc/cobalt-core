<?php

namespace Cobalt\SchemaPrototypes;

use ArrayAccess;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Iterator;
use MongoDB\Model\BSONArray;
use Validation\Exceptions\ValidationIssue;

class ArrayResult extends SchemaResult implements ArrayAccess, Iterator{
    use ResultTranslator;
    protected $type = "array";
    protected $__index = 0;

    function setValue($value):void {
        $array = $value;
        if($value instanceof BSONArray) $array = $value->getArrayCopy();

        $array = $this->__each($array, $this->__reference);

        $this->value = $array;
    }

    function eachToView(string $view, array $vars = []):string {
        $html = "";
        $fn = "view_from_string";
        if(template_exists($view)) $fn = "view";
        foreach($this->value as $val) {
            $html .= $fn($view, array_merge($vars,['doc' => $val]));
        }
        return $html;
    }

    function push() {
        $each = null;
        if(isset($this->schema['each'])) $each = $this->schema['each'];

        $args = func_get_args();

        if($each && $each instanceof SchemaResult) {
            array_push($this->value, ...$this->__each($args, $this->__reference, count($this->value)));
            return;
        }
        
        array_push($this->value, ...$args);
    }

    function pop() {
        return array_pop($this->value);
    }

    function shift() {
        return array_shift($this->value);
    }

    function unshift() {
        $each = null;
        if(isset($this->schema['each'])) $each = $this->schema['each'];

        $args = func_get_args();

        if($each && $each instanceof SchemaResult) {
            array_unshift($this->value, ...$this->__each($args, $this->__reference, count($this->value)));
            return;
        }

        array_unshift($this->value, ...$args);
    }

    function join($delimiter) {
        return implode($delimiter, $this->getValue());
    }

    public function __toString():string {
        return $this->join(", ");
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->getValue());
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->getValue()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        // $this->value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        // unset($this->value[$offset]);
    }

    public function current(): mixed {
        $val = $this->getValue();
        return $val[$this->key()];
    }

    public function next(): void {
        $this->__index++;
    }

    public function key(): mixed {
        $val = $this->getValue();
        $keys = array_keys($val);
        return $keys[$this->__index];
    }

    public function rewind(): void {
        $this->__index = 0;
    }

    function filter($value) {
        if(!is_array($value)) throw new ValidationIssue("Value must be an array");
        return $value;
    }
}