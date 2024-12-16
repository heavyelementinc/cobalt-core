<?php

namespace Cobalt\SchemaPrototypes;

use ArrayAccess;
use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Countable;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\Document;
use stdClass;
use Traversable;

class MapResult extends SchemaResult implements Iterator, Traversable, ArrayAccess, JsonSerializable, Countable {
    /**
     * @var GenericMap
     */
    protected $value;
    protected $type = "map";

    function filter($value) {
        return $this->value->validate($value);
    }

    function __isset($path) {
        return isset($this->value->{$path});
    }

    function __get($path) {
        return $this->value->{$path};
    }

    public function jsonSerialize(): mixed {
        return $this->value->jsonSerialize();
    }

    public function __toString(): string {
        return "[MapResultObject]";
    }

    public function __getStorable(): mixed {
        return $this->value->__dataset;
    }
    
    function setName(string $name) {
        $this->name = $name;
        $this->value = $this->__getInstancedMap(null, $this->schema['schema'] ?? [], "$this->name.");
    }

    function setSchema(?array $schema): void {
        $this->schema = array_merge(
            self::universalSchemaDirectives,
            $this->defaultSchemaValues(),
            $schema ?? []
        );
        // $this->value->schema;
    }

    function setValue(mixed $value): void {
        $this->originalValue = $value;
        $this->value = $this->value->ingest($value);
    }

    function __getInstancedMap($value):GenericMap {
        return new GenericMap($value, $this->schema['schema'] ?? [], "$this->name.");
    }

    function __getHydrated():array {
        return $this->value->__hydrated;
    }

    public function count(): int {
        return $this->value->count();
    }

    public function offsetExists(mixed $offset): bool {
        if(!$this->value) return false;
        return $this->value->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed {
        if(!$this->value) return null;
        return $this->value->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->value->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        $this->value->offsetUnset($offset);
    }

    /********* ITERATOR METHODS ***********/

    private $iteratorCurrentOffset = 0;

    public function current(): mixed {
        if(method_exists($this->value, "current")) return $this->value->current();
        return $this->value[$this->key()];
    }

    public function next(): void {
        if(method_exists($this->value, "next")) $this->value->next();
        else $this->iteratorCurrentOffset++;
    }

    public function key(): mixed {
        if(method_exists($this->value, "key")) return $this->value->key();
        $schema = $this->getSchema();
        $keys = array_keys($schema);
        return $keys[$this->iteratorCurrentOffset];
    }

    public function valid(): bool {
        if(method_exists($this->value, "valid")) return $this->value->valid();
        if(isset($this->value[$this->key()])) return true;
        return false;
    }

    public function rewind(): void {
        if(method_exists($this->value, "rewind")) $this->value->rewind();
        else $this->iteratorCurrentOffset = 0;
    }
}
