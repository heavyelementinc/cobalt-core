<?php

namespace Cobalt\Maps;

use ArrayAccess;
use ArrayObject;
use Cobalt\Maps\Exceptions\LookupFailure;
use Cobalt\Maps\Traits\Validatable;
use Cobalt\SchemaPrototypes\Compound\UploadImageResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Cobalt\SchemaPrototypes\Wrapper\DefaultUploadSchema;
use Countable;
use Drivers\Database;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use ReflectionObject;
use Traversable;
use TypeError;

/**
 * The __hydrated property is the ultimate authority. If a field does not exist in __hydrated, then it is not set.
 * 
 * @package Cobalt\Maps
 */
class GenericMap implements Iterator, Traversable, ArrayAccess, JsonSerializable, Countable {
    use ResultTranslator, Validatable;
    public array $__dataset = [];
    public string $namePrefix = "";
    public array $__hydrated = [];

    protected ?ObjectId $id = null;
    protected int $__current_index = 0;
    protected array $__schema = [];
    protected bool $__schemaHasBeenInitialized = false;
    protected array $__schemaFromConstructorArg = [];
    protected bool $__hasBeenRehydrated = false;
    

    private ?Database $manager = null;

    function __construct($document = null, array $schema = [], string $namePrefix = "") {
        $this->__namePrefix = $namePrefix;
        $this->__schemaFromConstructorArg = $schema ?? [];
        $this->__initialize_schema();
        if($document) $this->ingest($document);
    }

    protected bool $index_add_id_checkbox = false;
    
    function __set_index_checkbox_state(bool $state) {
        $this->index_add_id_checkbox = $state;
    }

    function __get_index_checkbox_state(): bool {
        return $this->index_add_id_checkbox;
    }

    public function __initialize_schema($schema = null): void {
        // $this->__schema = [];
        $schema = array_merge($this->__schemaFromConstructorArg, $schema ?? []);
        foreach($schema as $fieldName => $values) {
            if(is_array($values)) {
                if(key_exists(0, $values) && $values[0] instanceof SchemaResult) {
                    $values['type'] = $values[0];
                    unset($values[0]);
                }
                $this->__schema[$fieldName] = $values;
            }

            if($values instanceof SchemaResult) $this->__schema[$fieldName] = ['type' => $values];
        }
        $this->__schemaHasBeenInitialized = true;
    }

    public function readSchema():array {
        return $this->__schema;
    }

    /**
     * Do we need this??
     * @deprecated
     * @param string $name 
     * @param string $directive 
     * @return mixed 
     */
    public function getDirective(string $name, string $directive) {
        if(!key_exists($name, $this->__schema)) return null;
        if(!key_exists($directive, $this->__schema[$name])) {
            return $this->__schema[$name][$directive];
        } else if (key_exists('type', $this->__schema[$name])) {
            return $this->__schema[$name]['type']->getDirective($name);
        }
        return null;
    }

    public function ingest($values): GenericMap {
        if($values instanceof UploadImageResult) {
            $values = $values->get_image_result_format();
        }
        if($values instanceof GenericMap) {
            $this->__schema = array_merge($this->__schema, $values->readSchema());
            // if($this->__schema) $this->__schemaHasBeenInitialized = true;
            $values = $values->__dataset;
        }
        if(!$this->__schemaHasBeenInitialized) $this->__initialize_schema();
        if($values instanceof BSONDocument || $values instanceof BSONArray) $values = doc_to_array($values);
        if($values instanceof BSONArray) $values = $values->getArrayCopy();
        if(isset($values['_id'])) {
            $this->id = $values['_id'];
            unset($values['_id']);
        }
        $this->__dataset = $values; // Store our raw dataset because we have a ton of memory and we don't care.

        foreach($values as $field => $value) {
            $this->__rehydrate($field, $value, $this->__hydrated);
        }
        $this->__hasBeenRehydrated = true;
        return $this;
    }

    public function set_manager(Database $manager):void {
        $this->manager = $manager;
    }

    public function get_manager():Database {
        return $this->manager;
    }

    private function __rehydrate(string $field, mixed $value, array|BSONDocument|BSONArray &$target, ?array &$schema = null): void {
        if(!$this->__schemaHasBeenInitialized) throw new LookupFailure("Schema has not been initialized!");
        
        if(is_null($schema)) $schema = $this->__schema;
        
        // Set no schemaDirectives as default behavior
        $schemaDirectives = null;
        // Check if the field we're working on is explicity defined in our schema
        if(key_exists($field, $schema)) {
            // If it is, we need to read our schemaDirectives
            $schemaDirectives = $schema[$field];
        } else if(is_array($value) || $value instanceof ArrayObject) {
            // If it's an array or array object
            foreach($value as $i => $v) {
                if($v instanceof MapResult) {
                    $target[$field] = $v;
                    continue;
                }
                // Loop through them and rehydrate them
                if(is_iterable($v)) $this->__rehydrate($field.".$i", $v, $value[$i]);
            }
        }
        
        // Reference our target so we can recursively set values
        $target[$field] = $this->__toResult($field, $value, $schemaDirectives ?? [], $this);
        return;
    }

    ##### GETTERS & SETTERS #####

    public function getId() {
        return $this->id;
    }

    public function __get($name) {
        if($name === "_id") return $this->id;
        if(strpos($name, ".") >= 0) return lookup($name, $this);
        $result = $this->__hydrated[$name];
        return $result;
    }

    public function __set($name, $value) {
        $this->__dataset[$name] = $value;
        $this->__rehydrate($name, $value, $this->__hydrated);
        $this->__validatedFields[$name] = $value;
    }

    public function __isset($name) {
        if($name === "_id") return isset($this->id);
        $lookup = lookup($name, $this);
        if(strpos($name, ".") >= 0) return isset($lookup);
        return key_exists($name, $this->__hydrated);
    }

    public function __unset($name) {
        unset($this->__dataset[$name]);
        unset($this->__hydrated[$name]);
    }

    function __call($name, $arguments) {
        $schema = $this->__schema;
        $args = $arguments ?? [];
        if (key_exists($name, $schema) && is_callable($schema[$name])) {
            return $schema[$name]($this->getValue(), $this, ...$args);
        }
        if (method_exists($this, $name)) {
            if($this->__isPrototypeAttributeSet($this, $name) === false) throw new \BadFunctionCallException("Method lacks #[Prototype] attribute");
            return $this->{$name}(...$args);
        }
        throw new \BadFunctionCallException("Function `$name` does not exist on `$this->name`");
    }

    function __isPrototypeAttributeSet(GenericMap $class, string $methodName):?bool {
        $reflection = new ReflectionObject($class);
        $method = $reflection->getMethod($methodName);
        if(!$method) return null;//throw new \BadMethodCallException("Call for `$methodName` is invalid on `$this->name`");
        $attributes = $method->getAttributes();
        $validPrototypes = ["Prototype", "Cobalt\SchemaPrototypes\Traits\Prototype"];
        foreach($attributes as $attr) {
            if(in_array($attr->getName(), $validPrototypes)) return true;
        }
        return false;
    }

    ##### INTERFACE METHODS #####
    
    public function current(): mixed {
        return $this->__hydrated[$this->key()];
    }

    public function next(): void {
        $this->__current_index++;
    }

    public function key(): mixed {
        return array_keys($this->__hydrated)[$this->__current_index];
    }

    public function valid(): bool {
        if(count($this->__hydrated) >= $this->__current_index) return false;
        return true;
    }

    public function rewind(): void {
        $this->__current_index = 0;
    }

    public function offsetExists(mixed $offset): bool {
        if($offset === "_id") return isset($this->id);
        return isset($this->__hydrated[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        if($offset === "_id") return $this->id;
        return $this->__hydrated[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->__hydrated[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->__hydrated[$offset]);
    }

    public function jsonSerialize(): mixed {
        return $this->__dataset;
    }

    public function count(): int {
        return count($this->__hydrated);
    }
    
}