<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
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
        $target_name = ($this instanceof MixedType) ? $this->name : "[object Model]";
        throw new \BadFunctionCallException("Function `$name` does not exist on `$target_name`");
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

    #[Prototype]
    protected function getName() {
        return $this->name;
    }

    /**
     * This function returns the value serialized as JSON
     * @param bool $pretty if set to pretty then JSON_PRETTY_PRING and JSON_UNESCAPED_SLASHES will be passed to `json_encode`
     * @return string
     */
    #[Prototype]
     protected function json($pretty = false): string {
        if($this->__isPrivate()) return "";
        return json_encode($this->value, ($pretty) ? 0 : JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    #[Prototype]
    protected function json_pretty(): string {
        return $this->json(true);
    }

    /**
     * Depending on the defined `type` property, the this function will
     * return different results.
     *  * Strings return the character count
     *  * Numbers return the string-ified character count of the number
     *  * Arrays and other countables return the result of `count($var)`
     *  * Any other value types return null
     * @return int|null the length the string or countable
     */
    #[Prototype]
    protected function length(): int|null {
        if($this instanceof StringType) return strlen($this->getValue());
        else if ($this instanceof EnumType) return strlen($this->display());
        else if ($this instanceof NumberType) return abs($this->getValue());
        else {
            $val = $this->getValue();
            if (is_countable($val)) return count($val);
        }
        
        return 0;
    }

    #[Prototype]
    protected function getLabel($includeHtml = true): string {
        $labelStart = "<label>";
        $is_required = ($this->directiveOrNull("required")) ? " <span class=\"form-prototype--required-field\">" . __APP_SETTINGS__['Prototypeable_required_field_label'] . "</span>" : "";
        $labelEnd = "$is_required</label>";
        if($includeHtml === false) {
            $labelStart = "";
            $labelEnd = "";
        }
        $hasLabel = $this->hasDirective("label");
        if($hasLabel) return $labelStart.$this->getDirective("label") . $labelEnd;
        $split = str_replace([".","_"], " ", $this->name);
        return $labelStart . ucwords($split) . $labelEnd;
    }

    #[Prototype]
    protected function isRequired(): bool {
        if($this->hasDirective("required")) return $this->getDirective('required');
        return false;
    }

    
}