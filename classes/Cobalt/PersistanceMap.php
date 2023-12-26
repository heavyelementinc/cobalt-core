<?php

namespace Cobalt;

use ArrayAccess;
use Cobalt\SchemaPrototypes\PersistanceMapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\SubMapResult;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Exception;
use Exceptions\HTTP\BadRequest;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Document;
use stdClass;
use TypeError;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

/**
 * Schema
 * ======
 * The Cobalt Engine Normalization and Database Persistance Engine
 * 
 * The abstract Schema class is designed to persist across database storage
 * and retrieval, it provides a convenient method for mutating data in a 
 * predictable way, and an easy syntax for setting and getting data with 
 * prototypal inheritance for classes.
 * 
 * The following are definitions for valid schema fields
 * |:- field    -:|:- type -:|:- definition -:|
 * -------------------------------
 * | `get`        | callable | The `get` field |
 * 
 * Schemas will return field data as Schema<Type>Result objects. These
 * provide a convenient way to access and mutate data through prototypical
 * inheritance.
 * 
 * 
 * 
 * @package Cobalt
 */
abstract class PersistanceMap extends Validation implements Persistable, Iterator, ArrayAccess, JsonSerializable {
    use ResultTranslator;
    protected $id;
    public array $__dataset = [];
    private int $__current_index = 0;
    protected array $__schema;
    protected bool $__validateOnSet = true;

    /**
     * TODO: Implement hydration
     * @var array
     */
    protected array $__hydrated = [];
    protected bool $__hydrate = __APP_SETTINGS__['Schema_hydration_on_unserialize'];
    
    function __construct($document = null) {
        $this->id = new ObjectId;
        $this->__initialize_schema();
        if($document !== null) $this->ingest($document);
    }

    function __toString():string {
        return ""; // (string)$this->id;
    }

    abstract function __get_schema():array;

    function __initialize_schema():void {
        $this->__schema = [];
        $schema = $this->__get_schema();
        foreach($schema as $fieldName => $values) {
            if(is_array($values)) {
                if(key_exists(0, $values) && $values[0] instanceof SchemaResult) {
                    $values['type'] = $values[0];
                    unset($values[0]);
                }
                $this->__schema[$fieldName] = $values;
            }
            if($this instanceof SubMapResult) {
                if(!isset($this->__schema['schema'])) throw new Exception("PersistanceMapResult does not have a specified schema");
                if(!isset($this->__schema['map'])) {
                    $this->__schema['map'] = new SubMap();
                    $this->__schema['map']->__set_schema($this->__schema['schema']);
                }
                // $this->__map = $this->__schema['schema'];
            }
            if($values instanceof SchemaResult) $this->__schema[$fieldName] = ['type' => $values];
        }
    }

    public function __isset($name):bool {
        if($name === "_id") return true;
        if(key_exists($name, $this->__schema)) return true;
        if(key_exists($name, $this->__dataset)) return true;
        return false;
    }

    public function __get($name):PersistanceMap|SchemaResult|ObjectId {
        if(!$this->__schema) throw new TypeError("This Schema has not been initialized");
        if($name === "_id") return $this->id;
        
        if(key_exists($name, $this->__hydrated)) return $this->__hydrated[$name];
        $lookup = lookup_js_notation($name, $this->__dataset, false);
        $this->__hydrated[$name] = $this->__toResult($name, $lookup, $this->__schema[$name] ?? []);
        
        return $this->__hydrated[$name];
    }

    public function __set($name, mixed $value):void {
        if(!$this->__validateOnSet) $this->__dataset[$name] = $value;
        
        $result = $this->{$name};
        if($result instanceof SchemaResult) {
            $mutant = $result->filter($value);
            $this->__dataset[$name] = $mutant;
            if(isset($this->__hydrated[$name])) $this->__hydrated[$name]->setValue($mutant);
        } elseif ($result instanceof PersistanceMap) {
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

    /** @return array */
    function bsonSerialize(): array|\stdClass|Document {
        $serializationResult = $this->__dataset;
        return $serializationResult;
    }

    function bsonUnserialize(array $data): void {
        $this->__initialize_schema();
        $this->id = $data['_id'];
        unset($data['_id']);
        $this->__dataset = $data;
        if($this->__hydrate) return;
        foreach($this->__schema as $k => $v) {
            $r = lookup_js_notation($k, $data, false);
            $this->__hydrated[$k] = $this->__toResult($k, $r, $v);
        }
    }

    function jsonSerialize(): mixed {
        return array_merge(['_id' => $this->id], $this->bsonSerialize(), ['_id' => $this->id]);
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

    /**
     * 
     * @param array|Iterable $data 
     * @return PersistanceMap 
     */
    function ingest($data):PersistanceMap {
        if($data === null) $data = [];
        if(is_iterable($data) && !is_array($data)) $data = doc_to_array($data);
        if(!is_array($data)) throw new TypeError('$data must be an array or convertable into an array');
        if(!isset($data['_id'])) $data['_id'] = new ObjectId();

        $this->bsonUnserialize($data);
        return $this;
    }

    public function getId() {
        return $this->id;
    }
}