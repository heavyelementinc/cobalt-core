<?php

namespace MongoDB\BSON;

use IteratorAggregate;
use Serializable;
use Iterator;

class Document implements IteratorAggregate, Serializable{
    final private function __construct() {

    }
    final static public function fromBSON(string $bson): Document {
        return new self();
    }
    final static public function fromJSON(string $json): Document {
        return new self();
    }
    final static public function fromPHP(object|array $value): Document {
        return new self();
    }
    final public function get(string $key): mixed {

    }
    final public function getIterator(): Iterator {
        return new Iterator();
    }
    final public function has(string $key): bool {
        return !!$key;
    }
    final public function serialize(): string {
        return "";
    }
    final public function toCanonicalExtendedJSON(): string {
        return "";
    }
    final public function toPHP(?array $typeMap = null): array|object {
        return [];
    }
    final public function toRelaxedExtendedJSON(): string {
        return "";
    }
    final public function __toString(): string {
        return "";
    }
    final public function unserialize(string $data): void {

    }
}