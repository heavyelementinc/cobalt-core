<?php

namespace Cobalt\Maps;

use ArrayAccess;
use Cobalt\Maps\Exceptions\DirectiveException;
use Cobalt\Maps\Exceptions\LookupFailure;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Countable;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\ObjectId;
use stdClass;
use Traversable;
use TypeError;

class GenericMap extends Validation implements Iterator, Traversable, ArrayAccess, JsonSerializable, Countable {
    use ResultTranslator, NestedFind;

    protected $id;
    public array $__dataset = [];
    private int $__current_index = 0;
    protected array $__schema;
    protected ?array $__initialized_schema;
    protected bool $__schemaHasBeenInitialized = false;
    protected bool $__validateOnSet = true;
    protected bool $__strictFind = false; // If strictFind is true, only fields defined in __schema will be searched

    /**
     * TODO: Implement hydration
     * @var array
     */
    public array $__hydrated = [];
    protected bool $__hydrate = __APP_SETTINGS__['Schema_hydration_on_unserialize'];
    
    function __construct($document = null, array $schema = null) {
        $this->id = new ObjectId;
        $this->__initialize_schema($schema);
        if($document !== null) $this->ingest($document);
    }

    public function count(): int {
        return count($this->__dataset);
    }

    function __toString():string {
        return ""; // (string)$this->id;
    }

    function __initialize_schema($schema = null):void {
        $this->__schema = [];
        $mixin = [];
        if(method_exists($this, '__get_schema')) $mixin = $this->__get_schema();
        $schema = array_merge($mixin, $schema ?? [], $this->__initialized_schema ?? []);
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

    

    public function __get($name):GenericMap|SchemaResult|ObjectId {
        if(!$this->__schemaHasBeenInitialized) throw new TypeError("This Schema has not been initialized");
        if($name === "_id") return $this->id;
        
        // if(key_exists($name, $this->__hydrated)) return $this->__hydrated[$name];
        $lookup = $this->__getHydrated($name);
        $type = gettype($lookup);
        switch($type) {
            case "object":
                if($lookup instanceof SchemaResult) return $lookup;
                if($lookup instanceof GenericMap) return $lookup;
                if($lookup instanceof ObjectId) return $lookup;
                break;
            default:
                return $this->__toResult($name, $lookup, [], $this);
        }
        throw new LookupFailure("The value was of an unsupported type `$type`");
        // $this->__rehydrate($name, $lookup);//$this->__toResult($name, $lookup, $this->__schema[$name] ?? []);
        // return $this->__hydrated[$name];
        // return $lookup;// ?? new StringResult("");
    }

    private function __getHydrated($name) {
        return lookup_js_notation($name, $this->__hydrated, false);
    }

    public function __set($name, mixed $value):void {
        if(!$this->__validateOnSet) $this->__dataset[$name] = $value;
        
        $result = $this->{$name};
        if($result instanceof SchemaResult) {
            // Let's perform our validation routine.
            $mutant = $result->filter($value);

            // Let's check if the dev has provided a `store` directive
            if(key_exists("store", $this->__schema[$name])) {
                // We're doing this manually so we can throw an error if it's not callable.
                if(!is_callable($this->__schema[$name]['store'])) throw new DirectiveException("To specify the `store` directive for `$this->name`, it must be of type `callable`");
                $mutant = $this->__schema[$name]['store']($mutant, $this);
            }
            $this->__dataset[$name] = $mutant;
            if(isset($this->__hydrated[$name])) $this->__hydrated[$name]->setValue($mutant);
            
        } elseif ($result instanceof GenericMap) {
            $this->__dataset[$name] = $value;
            if(isset($this->__hydrated[$name])) $this->__hydrated[$name]->ingest($value);
        }
    }

    
    private function __get_prototype($name):array {
        $matches = [];
        $regex = "/(\w.+)*(\(.*\))?/";
        preg_match($regex, $name, $matches);
        return [str_replace($matches[0], "", $name), $matches[0]];
    }

    public function current(): mixed {
        return $this->__dataset[$this->__current_index];
    }

    public function next(): void {
        $this->__current_index++;
    }

    public function key(): mixed {
        return array_keys($this->__dataset)[$this->__current_index];
    }

    public function valid(): bool {
        return $this->__current_index > count($this->__dataset) - 1;
    }

    public function rewind(): void {
        $this->__current_index = 0;
    }

    public function offsetExists(mixed $offset): bool {
        if(isset($this->__schema[$offset])) return true;
        return false;
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->__dataset[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {
    $this->__dataset[$offset] = $value;
        return;
    }

    public function offsetUnset(mixed $offset): void { }

    
    function jsonSerialize(): mixed {
        $array = array_merge(['_id' => $this->id], $this->__dataset, ['_id' => $this->id]);
        $mutant = [];
        foreach($array as $field => $val) {
            if($val instanceof SchemaResult && $val->__isPrivate()) continue;
            $mutant[$field] = $val;
        }
        return $mutant;
    }

    /**
     * What is hydration? Hydration instances each field into its corresponding
     * SchemaResult wrapper at the time of deserialization rather than on demand
     * @param bool $value 
     * @return void 
     */
    function enableHydration(bool $value):void {
        $this->__hydrate = $value;
    }

    function __rehydrate(string $field, mixed $value): void {
        // if(key_exists($this->__hydrated))
        // $fieldPath = explode(".",$field);
        // if(count($fieldPath) === 1) {
        $result = $this->__toResult($field, $value, $this->__schema[$field] ?? [], $this);
        // if($result instanceof MapResult) {
        //     // $result = $result->__getHydrated();
        // }
        $this->__hydrated[$field] = $result;
        //     return;
        // }
    }

    /**
     * 
     * @param array|Iterable $data 
     * @return GenericMap 
     */
    function ingest($data):GenericMap {
        if($data === null) $data = [];
        if(is_iterable($data) && !is_array($data)) $data = doc_to_array($data);
        if(!is_array($data)) {
            if($data instanceof stdClass) {
                $data = get_object_vars($data);
            } else {
                throw new TypeError('$data must be an array or convertable into an array');
            }
        }
        if(!isset($data['_id'])) $data['_id'] = new ObjectId();

        $this->__initialize_schema();
        $this->id = $data['_id'];
        unset($data['_id']);
        $this->__dataset = $data;
        if(!$this->__hydrate) return $this;
        foreach($this->__schema as $k => $v) {
            $r = lookup_js_notation($k, $data, false);
            $this->__rehydrate($k, $r);
            // $this->__hydrated[$k] = $this->__toResult($k, $r, $v);
        }
        $this->__hydrated = Flatten::undot($this->__hydrated);
        return $this;
    }

    public function getId() {
        return $this->id;
    }

    public function getDirective(string $name, string $directive) {
        if(!key_exists($name, $this->__schema)) return null;
        if(!key_exists($directive, $this->__schema[$name])) {
            return $this->__schema[$name][$directive];
        } else if (key_exists('type', $this->__schema[$name])) {
            return $this->__schema[$name]['type']->getDirective($name);
        }
        return null;
    }
}