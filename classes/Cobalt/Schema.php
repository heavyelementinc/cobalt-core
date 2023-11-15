<?php

namespace Cobalt;

use ArrayAccess;
use Cobalt\SchemaPrototypes\ArrayResult;
use Cobalt\SchemaPrototypes\BooleanResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\IdResult;
use Cobalt\SchemaPrototypes\NumberResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\StringResult;
use Exceptions\HTTP\BadRequest;
use Iterator;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Document;
use PgSql\Lob;
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
abstract class Schema extends Validation implements Persistable, Iterator, ArrayAccess {
    private ObjectId $id;
    public array $__dataset = [];
    private int $__current_index = 0;
    protected array $__schema;

    function __construct() {
        $this->id = new ObjectID;
        $this->__initialize_schema();
    }

    function __toString():string {
        return (string)$this->id;
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
            if($values instanceof SchemaResult) $this->__schema[$fieldName] = ['type' => $values];
        }
    }

    public function __isset($name):bool {
        if(key_exists($name, $this->__schema)) return true;
        if(key_exists($name, $this->__dataset)) return true;
        return false;
    }

    public function __get($name):SchemaResult {
        if(!$this->__schema) throw new TypeError("This Schema has not been initialized");
        
        $lookup = lookup_js_notation($name, $this->__dataset, false);

        return $this->datatype_persistance($name, $lookup);
    }

    function datatype_persistance($name, $value):SchemaResult {
        $type = gettype($value);

        switch($type) {
            case (key_exists($name, $this->__schema) 
                && key_exists('type', $this->__schema[$name])
                && $this->__schema[$name]['type'] instanceof SchemaResult
            ):
                $result = $this->__schema[$name]['type'];
                break;
            case "string":
                $result = new StringResult();
                break;
            case "integer":
            case "number":
                $result = new NumberResult();
                break;
            case "array":
                $result = new ArrayResult();
                break;
            case "boolean":
                $result = new BooleanResult();
            case "object":
                switch(get_class($value)) {
                    case "\\MongoDB\\BSON\\Array":
                        $result = new ArrayResult();
                        break;
                    case "\\MongoDB\\BSON\\UTCDateTime":
                        $result = new DateResult();
                        break;
                    case "\\MongoDB\\BSON\\ObjectId":
                        $result = new IdResult();
                        break;
                    case "\\Cobalt\\Schema":
                        return $value;
                }
            default:
                $result = new SchemaResult();
                break;
        }

        $result->setName($name);
        $result->setSchema($this->__schema[$name]);
        $result->setValue($value);
        $result->datasetReference($this);

        return $result;
    }

    public function __set($name, mixed $value):void {

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

    function bsonSerialize(): array|\stdClass|Document {
        $serializationResult = array_merge($this->__dataset, [
            '_id' => $this->id,
        ]);
        return $serializationResult;
    }

    function bsonUnserialize(array $data): void {
        $this->__initialize_schema();
        $this->id = $data['_id'];
        $this->__dataset = $data;
    }

    /**
     * 
     * @param array|Iterable $data 
     * @return Schema 
     */
    function ingest($data):Schema {
        if(is_iterable($data) && !is_array($data)) $data = doc_to_array($data);
        if(!is_array($data)) throw new TypeError('$data must be an array or convertable into an array');
        if(!isset($data['_id'])) $data['_id'] = new ObjectId();

        $this->bsonUnserialize($data);
        return $this;
    }
}