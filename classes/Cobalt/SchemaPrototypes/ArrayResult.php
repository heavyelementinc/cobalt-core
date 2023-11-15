<?php

namespace Cobalt\SchemaPrototypes;

use ArrayAccess;
use Iterator;
use MongoDB\Model\BSONArray;
use Validation\Exceptions\ValidationIssue;

class ArrayResult extends SchemaResult implements ArrayAccess, Iterator{
    protected $type = "array";
    protected $__index = 0;

    function setValue($value):void {
        if($value instanceof BSONArray) $this->value = $value->getArrayCopy();
        $this->value = $value;
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