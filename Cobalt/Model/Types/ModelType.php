<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Classes\ValidationResults\MergeResult;
use Cobalt\Model\Exceptions\ImmutableTypeError;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;

class ModelType extends MixedType implements ArrayAccess {

    public function setValue($value):void {
        // Let's check if the value is already a Model (this could be because 
        // we) persisted some data from the DB, etc.
        if($value instanceof Model || $value instanceof GenericModel) {
            $value->name_prefix = $this->name;
            $this->value = $value;
            $this->isSet = true;
            return;
        }
        // Otherwise, we'll grab the schema for this value and we'll instance
        // a GenericModel
        $schema = ($this->hasDirective('schema')) ? $this->getDirective('schema') : [];
        // if($realKey && key_exists($realKey, $value)) $value = $value[$realKey];
        $this->value = new GenericModel($schema, $value, $this->name);
        // $this->value->name_prefix = $this->name;
        $this->isSet = true;
    }

    public function finalInitialization():void {
        if(isset($this->value)) return;
        $this->setValue([]);
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

    public function serialize() {
        if(is_null($this->value)) return [];
        return $this->value->serialize();
    }

    public function offsetExists(mixed $offset): bool {
        return $this->value?->offsetExists($offset) ?? false;
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

    /**
     * Filters input from the client before the input is stored in the database
     * @param mixed $value the user input
     * @return mixed Returns the value to the be stored, may be transformed 
     */
    public function filter($value) {
        if($this->isSet && $this->directiveOrNull(DIRECTIVE_KEY_IMMUTABLE)) throw new ImmutableTypeError("Cannot modify immutable field '$this->name'");
        if($this->hasDirective(DIRECTIVE_KEY_VALID)) {
            $this->getDirective(DIRECTIVE_KEY_VALID);
        }
        if($this->hasDirective(DIRECTIVE_KEY_FILTER)) $value = $this->getDirective(DIRECTIVE_KEY_FILTER, $value);
        $this->value->__filter($value);
        return new MergeResult(array_dot($this->value->getData(), (($this->value->name_prefix) ? $this->value->name_prefix."." : ""), false));
    }

    #[Directive()]
    public function defineSchema(array $schema):ModelType {
        $this->__defineDirective('schema', $schema);
        return $this;
    }
}