<?php

namespace Cobalt\Commands\Classes;

use Closure;
use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Exceptions\CommandError;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

class CommandItem {
    private string $name;
    private Object $instance;
    private string|Closure $function = "";
    private bool $doesMethodExist = false;
    private bool $isDefault = false;
    private array $flags = [];
    private string $description = "";

    function __construct(Object $instance, string $name, ?string $method = null, bool $default = false){
        $this->setInstance($instance);
        $this->setName($name);
        if(!$method) $method = $name;
        $this->setFunction($method);
        $this->setIsDefault($default);
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

        $reflection = new ReflectionMethod($this->instance, $this->function);
        $params = $reflection->getParameters();
        foreach($params as $index => $param) {
            $this->paramSanityCheck($param, $arguments[$index] ?? null);
        }

        return $this->instance->{$this->function}(...$arguments);
    }

    private function paramSanityCheck(ReflectionParameter $param, mixed $value = null) {
        $name = $param->getName();
        $pos = $param->getPosition();
        if($param->isOptional() === false && $value == null) {
            throw new CommandError("Parameter ".fmt("`\$$name`").fmt(" at position $pos is required.","e"));
        }
        if($param->hasType()) {
            if(gettype($value) !== (string)$param->getType()) throw new CommandError("Parameter `$"."$name` must be of type ".$param->getType());
        }
    }

    function getIsDefault():bool {
        return $this->isDefault;
    }

    function setIsDefault(bool $default) {
        $this->isDefault = $default;
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

    function getLongDescription() {
        $reflection = new ReflectionMethod($this->instance, $this->function);
        $attrs = $reflection->getAttributes();
        foreach($attrs as $attr) {
            if($attr->name === "Cobalt\\Commands\\Attributes\\LongDescription") return $attr->newInstance()->description;
        }
        return $this->getDescription();
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

    function renderVerboseCommandDetails():string {
        $details = [
            'arguments' => '',
            'flags' => '',
        ];
        $reflection = new ReflectionMethod($this->instance, $this->function);
        $args = $reflection->getParameters();
        for($i = count($args); $i >= 0; $i--) {
            $this->renderArgument($details['arguments'], $args[$i], $i, count($args));
        }
        $attrs = $reflection->getAttributes();
        foreach($attrs as $attr) {
            switch($attr->name) {
                case "Cobalt\\Commands\Attributes\\AcceptsFlags":
                    $details['flags'] = fmt("  Accepts the following flags:\n    ".implode("\n    ", $attr->newInstance()->accepts)."\n", "gray");
            }
        }
        return $details['arguments'] . "\n$details[flags]\n";
    }

    private function renderArgument(&$string, ?ReflectionParameter $param, int $index, int $max) {
        if($param == null) return;
        $name = '$'.$param->getName();
        if($index + 1 !== $max) $name .= "";
        $type = "";
        // if($param->hasType()) $type = $param->getType().": ";
        // $param->getAttributes();
        // if($param->)
        try {
            $default = "";
            if($param->isDefaultValueAvailable()) $default = $param->getDefaultValue();
        } catch(ReflectionException $e) {
            // $default = false;
        }
        $start = "";
        $stop = "";
        if($default == false) {
            $start = "[ ";
            $stop = "]";
        }
        $fmt = "$start"."$type";
        $string = sprintf(" %s%s%s%s", 
            fmt($fmt, 'grey',),
            fmt($name, "b"),
            $string,
            fmt(" $stop", 'grey')
        );
    }
}