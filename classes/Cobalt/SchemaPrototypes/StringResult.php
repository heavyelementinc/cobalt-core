<?php

namespace Cobalt\SchemaPrototypes;

use ArrayAccess;

class StringResult extends SchemaResult implements ArrayAccess{
    protected $type = "string";

    public function length():int|float|null {
        return strlen($this->value);
    }

    public function capitalize(){
        return ucfirst($this->value);
    }

    public function uppercase() {
        return strtoupper($this->value);
    }

    public function lowercase() {
        return strtolower($this->value);
    }

    public function last() {
        return $this->value[count($this->value) - 1];
    }

    public function display():string {
        $val = $this->getValue();
        $valid = $this->valid();
        if(key_exists($val, $valid)) return $valid[$val];
        if(key_exists($this->value, $valid)) return $valid[$this->value];
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
}