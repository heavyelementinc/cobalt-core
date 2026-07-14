<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Attributes\PrototypeMethod;
use Cobalt\DataModel\Classes\DirectiveList;
use Error;
use ReflectionMethod;
use ReflectionObject;
use TypeError;

trait GenericPrototypes {
    protected DirectiveList $directives;
    protected string $name;
    abstract public function getValue(): mixed;

    function json($pretty = false) {
        if($this->directives->hasDirective("private") && $this->directives['private']) return;
        return json_encode($this->getValue(), ($pretty) ? JSON_PRETTY_PRINT : 0);
    }

    function json_pretty() {
        return $this->json(true);
    }

    function getLabel($includeHtml = true, $small_text = ""):string {
        $is_required = $this->directives['required'] ?? false;
        $labelText = $this->directives['label'] ?? prettify_fieldname($this->name);
        if($includeHtml === false) {
            return $labelText . ($is_required) ? "*" : "";
        }

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
}