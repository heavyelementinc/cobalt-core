<?php

namespace Cobalt\Model\Traits;

use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\MixedType;
use ReflectionObject;

trait Prototypable {
    function __call($name, $arguments) {
        $directives = $this->directives ?? $this->schema ?? [];
        $args = $arguments ?? [];
        if (key_exists($name, $directives) && is_callable($directives[$name])) {
            return $directives[$name]($this->value, $this, ...$args);
        }
        if (method_exists($this, $name)) {
            if($this->__isPrototypeAttributeSet($this, $name) === false) throw new \BadFunctionCallException("Method lacks #[Prototype] attribute");
            return $this->{$name}(...$args);
        }
        throw new \BadFunctionCallException("Function `$name` does not exist on `$this->name`");
    }

    function __isPrototypeAttributeSet(MixedType|GenericModel $class, string $methodName):?bool {
        $reflection = new ReflectionObject($class);
        $method = $reflection->getMethod($methodName);
        if(!$method) return null;//throw new \BadMethodCallException("Call for `$methodName` is invalid on `$this->name`");
        $attributes = $method->getAttributes();
        $validPrototypes = ["Prototype", "Cobalt\Model\Attributes\Prototype"];
        foreach($attributes as $attr) {
            if(in_array($attr->getName(), $validPrototypes)) return true;
        }
        return false;
    }
}