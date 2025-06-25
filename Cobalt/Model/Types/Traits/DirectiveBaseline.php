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
        $d = [];
        if(method_exists($this,"initDirectives")) $d = $this->initDirectives();
        
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
                if($found === false) throw new DirectiveDefinitionFailure("Failed to define $directive. Defining method must have #[Directive] attribute.");
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
        if($this->directives[$name] instanceof AbstractDirective) return $this->directives[$name]->getValue(...$args);
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
    public function hasDirective($name) {
        return key_exists($name, $this->directives);
    }

    public function directiveOrNull($name) {
        if($this->hasDirective($name)) return $this->getDirective($name);
        return null;
    }

    public function directiveInstance($name) {
        if(!key_exists($name, $this->directives)) return $this->directives[$name];
        return null;
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
        $valid = [
            '$currentDate',
            '$inc',
            '$min',
            '$max',
            '$mul',
            '$rename',
            '$set',
            '$setOnInsert',
            '$unset',
        ];

        if($this instanceof ArrayType) {
            $valid = array_merge($valid, [
                '$', //Acts as a placeholder to update the first element that matches the query condition.
                '$[]', //Acts as a placeholder to update all elements in an array for the documents that match the query condition.
                '$[<identifier>]', //Acts as a placeholder to update all elements that match the arrayFilters condition for the documents that match the query condition.
                '$addToSet', //Adds elements to an array only if they do not already exist in the set.
                '$pop', //Removes the first or last item of an array.
                '$pull', //Removes all array elements that match a specified query.
                '$push', //Adds an item to an array.
                '$pullAll', //Removes all matching values from an array.
                '$each', //Modifies the $push and $addToSet operators to append multiple items for array updates.
                '$position', //Modifies the $push operator to specify the position in the array to add elements.
                '$slice', //Modifies the $push operator to limit the size of updated arrays.
                '$sort', //Modifies the $push operator to reorder documents stored in an array.
            ]);
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

    public function define_field($function):MixedType {
        $this->__defineDirective('field', new FieldDirective($function));
        return $this;
    }

}