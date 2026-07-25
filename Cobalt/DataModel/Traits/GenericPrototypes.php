<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Attributes\PrototypeMethod;
use Cobalt\DataModel\Classes\DirectiveList;
use Error;
use ReflectionMethod;
use ReflectionObject;
use TypeError;
use Cobalt\DataModel\Types\Generic;

/**
 * @mixin Generic
 */
trait GenericPrototypes {
    // protected DirectiveList $directives;
    protected string $name;
    abstract public function getValue(): mixed;

    function json($pretty = false) {
        if($this->directives->hasDirective("private") && $this->directives['private']) return;
        return json_encode($this->getValue(), ($pretty) ? JSON_PRETTY_PRINT : 0);
    }

    function json_pretty() {
        return $this->json(true);
    }

    #[PrototypeMethod]
    protected function getLabel($includeHtml = true, $small_text = ""):string {
        $is_required = $this->directives->required?->value ?? false;
        $labelText = $this->directives->label?->value ?? from_snake_case($this->getFieldDotNotation());
        if($includeHtml === false) return $labelText . ($is_required) ? "*" : "";

        // $labelStart

        return "";
    }

    function display() {
        if($this->directives->hasDirective('valid')) {
            return $this->directives->valid->display();
        }
        return $this->getValue();
    }

    function getValid():?array {
        if($this->directives->hasDirective('valid')) {
            return $this->directives->valid->normalized();
        }
        return null;
    }

    function options(mixed $preselected = null, array $arbitrary_additional_values = []):?string {
        if($this->directives->hasDirective('valid')) {
            return $this->directives->valid->options($preselected, $arbitrary_additional_values);
        }
        return null;
    }
    
    /**
     * Any prototype method that needs setup prior to being called should be
     * declared a `protected` or `private` method and must be assigned the
     * #[PrototypeMethod] attribute
     * 
     * Note that *all prototype methods* should be documented
     * 
     * @param string $name 
     * @param array $arguments 
     * @return mixed 
     * @throws TypeError 
     */
    function __call(string $name, array $arguments):mixed {
        $protoFail = new TypeError("Method $name does not exist on `\$this`");
        if(!method_exists($this, $name)) throw $protoFail;
        if(get_cfg_var("opcache.save_comments") === false) {
            throw new Error('comments must be saved');
        }
        $method = new ReflectionMethod($this, $name);
        $attributes = $method->getAttributes(PrototypeMethod::class);
        if(count($attributes) == 0) throw $protoFail;
        return $this->{$name}(...$arguments);
    }

    /**
     * If you need to find a specific generic that may be nested multiple
     * DictionaryTypes deep, you can call __lookup with a dot notated name and
     * it should return a specific URL
     * @param string $name 
     * @return null|Generic 
     */
    function __lookup(string $name):?Generic {
        $split = explode(".", $name);
        /** @var Generic $candidate */
        $candidate = $this;
        foreach($split as $value) {
            if(!isset($candidate->{$value})) return null;
            if($candidate->{$value} instanceof Generic === false) return null;
            /** @var Generic $candidate */
            $candidate = $candidate->{$value};
        }
        if(!isset($value) || $candidate->name !== $value) return null;
        return $candidate;
    }
}