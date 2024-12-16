<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\Model\Exceptions\ImmutableTypeError;
use Cobalt\Model\Exceptions\Undefined;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Traits\Directives;
use Cobalt\Model\Traits\Filterable;
use Cobalt\Model\Traits\Prototypable;
use Stringable;

class MixedType implements Stringable, ArrayAccess {
    use Prototypable, Filterable, Directives;
    protected bool $isSet = false;
    protected $value;
    protected string $name;
    protected bool $hasModel = false;
    protected GenericModel $model;

    /**
     * The getValue() function will return the present value or the 
     * 'default' directive if it's not set. If no default is set, null
     * is returned
     * @return void|mixed 
     */
    public function getValue() {
        if(!$this->isSet) return ($this->hasDirective('default')) ? $this->getDirective("default") : null;
        if(!$this->value) return ($this->hasDirective('default')) ? $this->getDirective("default") : null;
        return $this->value;
    }

    public function setValue($value):void {
        if($this->isSet && $this->getDirective('immutable')) throw new ImmutableTypeError("This value is considered immutable and must not be changed.");
        $this->value = $value;
        $this->isSet = true;
    }

    public function setName(string $name):void {
        $this->name = $name;
    }

    public function setModel(GenericModel $model):void {
        $this->model = $model;
    }

    /**
     * Filters input from the client before the input is stored in the database
     * @param mixed $value the user input
     * @return mixed Returns the value to the be stored, may be transformed 
     */
    public function filter($value) {
        if($this->isSet && $this->getDirective('immutable')) throw new ImmutableTypeError("Cannot modify immutable field '$this->name'");
        if($this->hasDirective('valid')) {
            $this->getDirective('valid');
        }
        if($this->hasDirective('filter')) $value = $this->getDirective('filter', $value);
        return $value;
    }
    

    /*************** OVERLOADING  ***************/
    public function __get($property) {
        switch($property) {
            case "value":
                return $this->getValue();
            case "raw":
            case "original":
                return $this->originalValue;
            case "model":
                return $this->model;
            case "type":
                return gettype($this->value);
            case "name":
                return $this->name;
            default:
                return null;
        }
    }

    public function __isset($property) {
        switch($property) {
            case "value":
                return $this->hasDirective('defaultValue') || $this->isSet;
            case "raw":
            case "original":
                return $this->isSet;
            case "name":
                return isset($this->name);
            case "model":
                return $this->hasModel;
            default:
                return false;
        }
    }

    public function __set($property, $value) {
        switch($property) {
            case "value":
                $this->__filter($value);
                break;
            // case "raw":
            // case "original":
            //     return $this->isSet;
            // case "name":
            //     return isset($this->name);
            // case "model":
            //     return $this->hasModel;
            default:
                // return false;
                throw new Undefined($property, "Cannot set $property.");
        }
    }

    public function __unset($property) {
        switch($property) {
            case "value":
                unset($this->value);
                break;
            default:
                throw new Undefined($property, "Property `$property` does not exist");
        }
    }

    public function __toString(): string {
        return (string)$this->getValue();
    }

    public function __getStorable() {
        return $this->value;
    }

    public function offsetExists(mixed $offset): bool {
        return $this->__isset($offset);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        $this->__unset($offset);
    }
}