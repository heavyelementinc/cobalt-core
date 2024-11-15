<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Model;
use Error;
use Stringable;

class MixedType implements Stringable {
    protected bool $isSet = false;
    protected $value;
    protected array $directives = [];

    public function getValue() {
        return $this->value;
    }

    public function setValue($value):void {
        $this->isSet = true;
        $this->value = $value;
    }

    protected bool $hasModel = false;
    protected Model $model;

    public function setModel(Model $model):void {
        $this->model = $model;
    }

    public function setDirectives(array $directives) {
        $this->directives = $directives;
    }

    /**
     * @param string $directive - The name of the directive you want
     * @return void 
     */
    public function getDirective() {
        $args = func_get_args();
        $name = array_shift($args);
        if(!key_exists($name,$this->directives)) throw new Error("No directive exists by the name `$name`");
        // Let's check if the directive is a function or not
        if(is_function($this->directives[$name])) {
            return $this->directives[$name](...$args);
        }
        return $this->directives[$name];
    }

    /*************** OVERLOADING  ***************/
    public function __get($property) {
        switch($property) {
            case "raw":
            case "original":
                return $this->originalValue;
            case "value":
                return $this->value;
            case "model":
                return $this->model;
            case "type":
                return gettype($this->value);
            default:
                return null;
        }
    }

    public function __isset($property) {
        switch($property) {
            case "raw":
            case "original":
            case "value":
                return $this->isSet;
            case "model":
                return $this->hasModel;
            default:
                return false;
        }
    }

    public function __toString(): string {
        return (string)$this->value;
    }
}