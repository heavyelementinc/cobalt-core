<?php

namespace Cobalt\Commands\Classes;

use Closure;
use Cobalt\Commands\Attributes\Description;
use ReflectionClass;
use ReflectionMethod;

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

    public function renderCommandDetails(int $cmdPadding) {
        print(" " . fmt(str_pad($this->name, $cmdPadding + 2),"i"));
        print($this->getDescription());
        print("\n");
    }

    public function exec(array $arguments, array $flags):int {
        // Handle delta functions
        if($this->function instanceof Closure) {
            return call_user_func($this->function, ...$arguments);
        }

        // Handle flag preprocessing, if necessary
        if(isset($this->instance) && $this->instance instanceof CommandInterface) {
            $this->instance->handleFlags($flags, $this, $this->function, $arguments);
        }
        return $this->instance->{$this->function}(...$arguments);
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
            if(method_exists($this->instance, $value)) {
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
        if(isset($this->function) && method_exists($value, $this->function)) {
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
        if(!$this->description && $this->doesMethodExist && is_string($this->function)) {
            $reflection = new ReflectionMethod($this->instance, $this->function);
            $attrs = $reflection->getAttributes();
            foreach($attrs as $attr) {
                if($attr->name === "Cobalt\\Commands\\Attributes\\Description") return $attr->newInstance()->description;
            }
            return "";
        }
        return $this->description;
    }
    function setDescription(string $value):CommandItem {
        $this->description = $value;
        return $this;
    }
}