<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;

class ModelType extends MixedType implements ArrayAccess {

    public function setValue($value):void {
        // Let's check if the value is already a Model (this could be because 
        // we) persisted some data from the DB, etc.
        if($value instanceof Model) {
            $this->value = $value;
            $this->isSet = true;
            return;
        }
        // Otherwise, we'll grab the schema for this value and we'll instance
        // a GenericModel
        $schema = ($this->hasDirective('schema')) ? $this->getDirective('schema') : [];
        $this->value = new GenericModel($schema, $value);
        $this->isSet = true;
    }

    public function __get($name) {
        if(isset($this->value->{$name})) return $this->value->{$name};
        return parent::__get($name);
    }

    public function __set($name, $value) {
        $this->value->{$name} = $value;
    }

    public function __isset($property) {
        return isset($this->value->{$property});
    }

    public function __getStorable() {
        return $this->value->getData();
    }

    public function offsetExists(mixed $offset): bool {
        return $this->value->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->value->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        $this->value->offsetUnset($offset);
    }
}