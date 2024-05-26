<?php

namespace Cobalt\Maps;

use ArrayAccess;
use Cobalt\Maps\Traits\Validatable;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Iterator;
use Traversable;
use JsonSerializable;
use Countable;
use MongoDB\BSON\ObjectId;

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
    protected bool $__schemaHasBeenInitialized = false;
    protected array $__schemaFromConstructorArg = [];

    public array $__hydrated =[];
    protected bool $__hasBeenHyydrated = false;

    protected ?ObjectId $id = null;

    function __construct($document = null, array $schema = [], string $namePrefix = "") {
        if($document) $this->ingest($document);
    }

    public function __initialize_schema(): void { }

    public function ingest(array $values): GenericMap2 {
        return $this;
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