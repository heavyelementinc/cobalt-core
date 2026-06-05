<?php

namespace Cobalt\Commands\Classes;

use Closure;

class CommandItem {
    private string $name;
    private Object $instance;
    private string|Closure $function = "";
    private bool $doesMethodExist = false;
    private array $flags = [];
    private string $description = "";

    function __construct(Object $instance, string $name, ?string $method = null){
        $this->setInstance($instance);
        $this->setName($name);
        if(!$method) $method = $name;
        $this->setFunction($method);
    }

    function getName():string {
        return $this->name;
    }
    function setName(string $value):CommandItem {
        $this->name = $value;
        return $this;
    }

    function getFunction():string|Closure {
        return $this->function;
    }
    function setFunction(string|Closure $value):CommandItem {
        if($value instanceof Closure) {
            $this->doesMethodExist = true;
        } else if(isset($this->instance)) {
            if(property_exists($this->instance, $value)) {
                $this->doesMethodExist = true;
            }
        }
        $this->function = $value;
        return $this;
    }

    function getInstance():Object {
        return $this->instance;
    }
    function setInstance(Object $value):CommandItem {
        if(isset($this->function) && property_exists($value, $this->function)) {
            $this->doesMethodExist = true;
        }
        $this->instance = $value;
        return $this;
    }

    function getFlags():array {
        return $this->flags;
    }
    function setFlags(array $value):CommandItem {
        $this->flags = $value;
        return $this;
    }

    function getDescription():string {
        return $this->description;
    }
    function setDescription(string $value):CommandItem {
        $this->description = $value;
        return $this;
    }
}