<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Model;
use Error;
use Stringable;

class MixedType implements Stringable {
    protected bool $isSet = false;
    protected $value;
    protected string $name;
    protected bool $hasModel = false;
    protected Model $model;

    protected array $directives = [];

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
        $this->value = $value;
        $this->isSet = true;
    }

    public function setName(string $name):void {
        $this->name = $name;
    }

    public function setModel(Model $model):void {
        $this->model = $model;
    }

    public function setDirectives(array $directives) {
        $this->directives = $directives;
        unset($this->directives['type']);
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

    public function hasDirective($name) {
        return key_exists($name, $this->directives);
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
                return $this->hasDirective('default') || $this->isSet;
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

    public function __toString(): string {
        return (string)$this->getValue();
    }
}