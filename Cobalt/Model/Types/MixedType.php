<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Cobalt\Model\Traits\Prototypable;
use Error;
use ReflectionObject;
use Stringable;

class MixedType implements Stringable {
    use Prototypable;
    protected bool $isSet = false;
    protected $value;
    protected string $name;
    protected bool $hasModel = false;
    protected GenericModel $model;

    // Here we provide some sane defaults
    protected array $directives = [
        'asHTML' => false,
    ];

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

    public function setModel(GenericModel $model):void {
        $this->model = $model;
    }

    public function setDirectives(array $directives) {
        $this->directives = array_merge($this->directives, $directives);
        unset($this->directives['type']);
    }

    /**
     * @param string $directive - The name of the directive you want 
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

    public function __getStorable() {
        return $this->value;
    }
}