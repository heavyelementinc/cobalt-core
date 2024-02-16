<?php

namespace Cobalt\SchemaPrototypes;

use ArrayAccess;
use Cobalt\Maps\GenericMap;
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
    // function setName(string $name) {
    //     // TODO: Set the appropriate name
    // }

    // function setSchema(?array $schema): void {
    //     $this->schema = array_merge(
    //         self::universalSchemaDirectives,
    //         $this->defaultSchemaValues(),
    //         $schema ?? []
    //     );
    //     // $this->value->schema;
    // }

    function setValue(mixed $value): void {
        $this->originalValue = $value;
        $this->value = new GenericMap($value, $this->schema['schema'] ?? [], "$this->name.");
    }

    function __getHydrated():array {
        return $this->value->__hydrated;
    }


    
    public function count(): int {
        return $this->value->count();
    }

    public function offsetExists(mixed $offset): bool {
        return $this->value->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->value->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        $this->value->offsetUnset($offset);
    }

    public function current(): mixed {
        return $this->value->current();
    }

    public function next(): void {
        $this->value->next();
    }

    public function key(): mixed {
        return $this->value->key();
    }

    public function valid(): bool {
        if(!$this->value) return false;
        return $this->value->valid();
    }

    public function rewind(): void {
        if($this->value) $this->value->rewind();
    }
}
