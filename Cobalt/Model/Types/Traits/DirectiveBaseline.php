<?php
declare(strict_types=1);

namespace Cobalt\Model\Types\Traits;

use Closure;
use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Cobalt\Model\Directives\FieldDirective;
use Cobalt\Model\Directives\SetDirective;
use Cobalt\Model\Exceptions\DirectiveDefinitionFailure;
use Cobalt\Model\Exceptions\InvalidUpdateOperator;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BinaryType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\NumberType;
use Error;
use ReflectionMethod;
use ReflectionObject;

enum Operators {
    
}

trait DirectiveBaseline {
    public function setDirectives(array $directives) {
        $d = $this->initDirectives();
        
        foreach(array_merge($this->directives, $d, $directives) as $directive => $value) {
            $directive_name = "define_$directive";
            if(method_exists($this, $directive_name)) {
                $reflection = new ReflectionObject($this);
                $method = $reflection->getMethod($directive_name);
                if(!$method) return null;
                $attributes = $method->getAttributes();
                $validPrototypes = ["Directive", "Cobalt\Model\Attributes\Directive"];
                $found = false;
                foreach($attributes as $attr) {
                    if(in_array($attr->getName(), $validPrototypes)) $found = true;
                }
                if($found === false) throw new DirectiveDefinitionFailure("Failed to define directive '$directive'. Defining method must have #[Directive] attribute.");
                $this->{$directive_name}($value, $directive);
            }
            else $this->__defineDirective($directive, $value);
        }
        unset($this->directives['type']);
    }
    
    public function initDirectives(): array {
        return [];
    }

    /**
     * Gets the directive or throws an exception if it's not available
     * 
     * **You can use `directiveOrNull()` if you don't care if the directive exists** 
     * @param string $directive - The name of the directive you want 
     */
    public function getDirective($name, &...$args) {
        // $name = array_shift($args);
        if(!key_exists($name, $this->directives)) throw new Error("Error on `".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."`: Directive `$name` does not exist.");
        if($this->directives[$name] instanceof AbstractDirective) return $this->directives[$name]->getValue(...func_get_args());
        // Let's check if the directive is a function or not
        if(is_function($this->directives[$name])) {
            return $this->directives[$name](...$args);
        }
        return $this->directives[$name];
    }

    /**
     * Tests to see if the directive exists
     * 
     * **You can use `directiveOrNull()` if you don't care if the directive exists** 
     * @param mixed $name 
     * @return bool 
     */
    public function hasDirective($name):bool {
        return key_exists($name, $this->directives);
    }

    public function directiveOrNull($name) {
        if($this->hasDirective($name)) return $this->getDirective($name);
        return null;
    }

    public function directiveInstance($name):?AbstractDirective {
        if(!key_exists($name, $this->directives)) return null;
        return $this->directives[$name];
    }
    
    // Here we provide some sane defaults
    protected array $directives = [
        # 'defaultValue' => null, // We're enumerating this here but commenting it out.
        
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
        'skipValidation' => false,
        
        /** @var bool 'filter' */
        #'filter' => fn ($val) => $val,
    ];

    public function __defineDirective($name, $value) {
        $this->directives[$name] = $value;
    }

    #[Directive]
    public function define_default(mixed $value):MixedType {
        $this->__defineDirective('default', $value);
        return $this;
    }

    #[Directive]
    public function define_asHTML(bool $value):MixedType {
        $this->__defineDirective('asHTML', $value);
        return $this;
    }

    #[Directive]
    public function define_immutable(bool $value):MixedType {
        $this->__defineDirective('immutable', $value);
        return $this;
    }

    protected string $operator = '$set';
    #[Directive]
    public function define_operator(string|Closure $operator):MixedType {
        if($operator instanceof Closure) {
            $this->__defineDirective('operator', $operator);
            return $this;
        }
        $valid = DATABASE_UPDATE_OPERATORS;

        if($this instanceof ArrayType) {
            $valid = array_merge($valid, DATABASE_ARRAY_UPDATE_OPERATORS);
        } else if($this instanceof BinaryType || $this instanceof NumberType){
            $valid[] = '$bit';
        }

        if(!in_array($operator, $valid)) throw new InvalidUpdateOperator("Operator `$operator` is invalid for this field");
        
        $this->__defineDirective('operator', function (&$operators, &$field, &$details) {
            $operators[$this->operator][$field] = $details;
        });
        return $this;
    }

    #[Directive]
    public function define_filter($function):MixedType {
        $this->__defineDirective('filter', $function);
        return $this;
    }

    #[Directive]
    public function define_field($function):MixedType {
        $this->__defineDirective('field', $function);
        // $this->__defineDirective('field', new FieldDirective($function));
        return $this;
    }

    #[Directive]
    public function define_description($function):MixedType {
        $this->__defineDirective('description', $function);
        return $this;
    }

    #[Directive()]
    public function defineskipValidation($function):MixedType {
        $this->__defineDirective('skipValidation', $function);
        return $this;
    }

    // #[Directive]
    // public function define_valid($function):MixedType {
    //     $this->__defineDirective('valid', $function);
    //     return $this;
    // }

}