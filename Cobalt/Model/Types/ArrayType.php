<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\Model\Traits\Defineable;

class ArrayType extends MixedType implements ArrayAccess {
    use Defineable;

    public function setValue($array):void {
        $this->value = [];
        foreach($array as $index => $value) {
            $this->define($this->value, $index, $value, null, $this->model, $this->name."[".$index."]");
        }
        $this->isSet = true;
    }

    public function __toString(): string {
        return join(", ", $this->__getStorable());
    }

    public function __getStorable() {
        $value = [];
        foreach($this->value as $i => $v) {
            $value[$i] = $v->__getStorable();
        }
        return $value;
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->value);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->define($this->value, $offset, $value, null, $this->model, $this->name."[".$offset."]");
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->value[$offset]);
    }
}