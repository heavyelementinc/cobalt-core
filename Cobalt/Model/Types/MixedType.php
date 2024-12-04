<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Exceptions\ImmutableTypeError;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Cobalt\Model\Traits\Filterable;
use Cobalt\Model\Traits\Prototypable;
use Error;
use ReflectionObject;
use Stringable;

class MixedType implements Stringable {
    use Prototypable, Filterable;
    protected bool $isSet = false;
    protected $value;
    protected string $name;
    protected bool $hasModel = false;
    protected GenericModel $model;

    // Here we provide some sane defaults
    protected array $directives = [
        # 'default' => null, // We're enumerating this here but commenting it out.
        
        /** @var bool 'asHTML' controls whether the value of this type is HTML escaped or not before being rendered */
        'asHTML' => false,
        
        /** @var bool 'immutable' types prevent the changing of a value once it's set
         * @todo Make the immutable directive also control the mutability when filtering user input
         */
        'immutable' => false,

        /** @var bool 'operator' By default all types use the MongoDB '$set' operator
         * You may specify any other valid MongoDB update operator https://www.mongodb.com/docs/manual/reference/operator/update/
         */
        'operator' => '$set',

        /** @var bool 'filter' */
        #'filter' => fn ($val) => $val,
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

    public function setDirectives(array $directives) {
        $d = [];
        if(method_exists($this,"initDirectives")) $d = $this->initDirectives();
        $this->directives = array_merge($this->directives, $d, $directives);
        unset($this->directives['type']);
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