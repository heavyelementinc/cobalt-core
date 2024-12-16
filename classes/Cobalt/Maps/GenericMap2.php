<?php

namespace Cobalt\Maps;

use ArrayAccess;
use Cobalt\Maps\Exceptions\LookupFailure;
use Cobalt\Maps\Traits\Validatable;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Iterator;
use Traversable;
use JsonSerializable;
use Countable;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

/**
 * A GenericMap is a means of enforcing schemas, data validation and logic on
 * data.
 * 
 * Lifecycle of data:
 *   - GenericMap is invoked and passed an array as the first arg
 *     - Alternatively, the GenericMap may be called without args and GenericMap::ingest() may be used
 *   - The ingest function will hydrate the data and store hydrated values in __hydrated
 *   - The __hydrated property is considered the source of truth
 *   - 
 * 
 * @package Cobalt\Maps
 */

class GenericMap2 implements Iterator, Traversable, ArrayAccess, JsonSerializable, Countable {
    use ResultTranslator, Validatable;

    protected array $__schema = [];
    protected bool $__hasSchemaBeenInitialized = false;
    protected array $__schemaFromConstructorArg = [];

    public array $__hydrated =[];
    protected bool $__hasBeenHyydrated = false;

    protected $__current_index = 0;

    protected ?ObjectId $id = null;

    function __construct($document = null, array $schema = [], string $namePrefix = "") {
        $this->__namePrefix = $namePrefix;
        $this->__schemaFromConstructorArg = $schema;
        $this->__initialize_schema();
        if($document) $this->ingest($document);
    }

    public function __initialize_schema(?array $schema = []): void {
        $schema = array_merge($this->__schemaFromConstructorArg, $schema);
        foreach($schema as $fieldName => $values) {
            if(is_array($values)) {
                // Check if the first index is a SchemaResult and move it to the ['type'] key
                // For example ['someFieldName' => [new SchemaResult()]]
                if(key_exists(0, $values) && $values[0] instanceof SchemaResult) {
                    $values['type'] = $values[0];
                    unset($values[0]);
                }
                // We've now initialized our schema, so let's store it as our
                $this->__schema[$fieldName] = $values;
            } else if($values instanceof SchemaResult) {
                // If we're shorthand applying the type to the schema, then we
                // need to convert that into a valid array with the correct key
                // For example ['someFieldName' => new SchemaResult()]
                $this->__schema[$fieldName] = ['type' => $values];
            }
        }
        $this->__hasSchemaBeenInitialized = true;
    }

    function get_schema():array {
        return $this->__schema;
    }

    /**
     * @deprecated
     */
    function readSchema() {
        return $this->get_schema();
    }

    public function ingest(BSONDocument|BSONArray|iterable $values): GenericMap2 {
        if($values instanceof GenericMap) {
            $this->__schema = array_merge($this->__schema, $values->get_schema());
            $values = $values->__dataset;
        }
        if(!$this->__hasSchemaBeenInitialized) $this->__initialize_schema();
        if($values instanceof BSONDocument || $values instanceof BSONArray) $values = doc_to_array($values);
        // if($values instanceof BSONArray) $values = $values->getArrayCopy();
        
        // Now that we have normalized our $values datastructure into an known state
        // let's start hydrating it.
        if(isset($values['_id'])) {
            $this->id = $values['_id'];
            unset($values['_id']);
        }

        foreach($values as $field => $value) {
            $this->__hydrate($field, $value, $this->__hydrated, $this->__schema);
        }

        return $this;
    }

    ##### INGEST FUNCTIONS #####
    private function __hydrate(string $field, mixed $value, array &$target, array $schema):void {
        if(!$this->__hasSchemaBeenInitialized) throw new LookupFailure("Schema has not been initialized!");
        if(strpos($field, ".", 0) !== false) {
            $this->__inflateDotNotatedField($field, $value, $target, $schema[$field] ?? [], $schema);
        }
        $schemaDirectives = null;
        if(key_exists($field, $target)) {
            $schemaDirectives = $target[$field];

        }
    }

    private function __isFieldInSchema(string $field, array $target):bool {
        return key_exists($field, $target);
    }

    private function __inflateDotNotatedField(string $field, $value, array $target, array $schema, array $parent_schema):void {
 
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