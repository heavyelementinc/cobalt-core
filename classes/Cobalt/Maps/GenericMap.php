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
use TypeError;

class GenericMap extends Validation implements Iterator, ArrayAccess, JsonSerializable, Countable {
    use ResultTranslator;

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

    public function __isset($name):bool {
        if(key_exists($name, $this->__hydrated)) return true;
        if(key_exists($name, $this->__schema)) return true;
        if(key_exists($name, $this->__dataset)) return true;
        $nestedFindResult = false;
        // if(strpos($name, ".")) $nestedFindResult = $this->__nestedFind($name);
        // if($nestedFindResult) return true;
        
        // // // if($this->__setChecker($name, $this->__strictFind)) return true;
        // if(strpos($name, ".")) return $this->__isset_deep($name);

        return false;
    }

    // function __nestedFind($name) {
    //     // Let's say $name = "media"
    //     foreach($this->__schema as $field => $value) {
    //         // Eventually, $field === "media.filename"
    //         if(strpos($field, ".") === false) continue; // If this field is not a dot notation path, continue
    //         if(strpos($field, $name) === 0) {
    //             if(key_exists($name, $this->__dataset)) return true;
    //         }
    //     }
    //     return false;
    // }

    // function __isset_deep($name) {
    //     $explodedName = explode(".", $name);
    //     $found = "";
    //     $candidate = [$this, null, ""];
    //     while(count($explodedName) > 0) {
    //         $currentPath = array_shift($explodedName);
    //         $remainingPath = "$currentPath.". implode(".", $explodedName);
    //         $candidate[1] = null;
    //         $candidate[2] = "";

    //         if($candidate[0] instanceof SchemaResult) {
    //             $candidate = $this->__handleSchemaResult($candidate[0], $currentPath, $remainingPath);
    //         }

    //         if($candidate[0] instanceof GenericMap) {
    //             $candidate = $this->__handleGenericMap($candidate[0], $currentPath, $remainingPath);
    //         }
            
    //         if($candidate[1] === true) $found .= ($found) ? ".$candidate[2]" : "$candidate[2]";
    //     }
    //     if($name === $found) return true;
    //     return false;
    // }

    // function __handleGenericMap($candidate, $name, $remaining) {
    //     $isset = isset($candidate[$name]);
    //     if($isset) return [$candidate[$name], true, $name];
    //     $isset = isset($candidate[$remaining]);
    //     if($isset) return [$candidate[$remaining], true, $remaining];
    //     return [$candidate, false, null];
    // }

    // function __handleSchemaResult($candidate, $name, $remaining) {
    //     if($candidate instanceof MapResult) return [$candidate->getRaw(), null, ""];
    //     return [$candidate, null, ""];
    // }

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
        if(!is_array($data)) throw new TypeError('$data must be an array or convertable into an array');
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
}