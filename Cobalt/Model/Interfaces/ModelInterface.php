<?php

namespace Cobalt\Model\Interfaces;

use Iterator;
use MongoDB\BSON\Document;
use MongoDB\Model\BSONDocument;
use stdClass;

interface ModelInterface {
    public function modelSerialize():array|stdClass|Document;

    public function modelUnserialize(array|BSONDocument $document);

    // public function __get(string $fieldName):mixed;

    // public function __set(string $fieldName, mixed $value);

    // public function __isset(string $fieldName):bool;

    // public function __unset(string $fieldName):void;

    // public function jsonSerialize(): mixed;

    // public function current(): mixed;

    // public function next(): void;

    // public function key(): mixed;

    // public function valid(): bool;

    // public function rewind(): void;

    // public function offsetExists(mixed $offset): bool;

    // public function offsetGet(mixed $offset): mixed;

    // public function offsetSet(mixed $offset, mixed $value): void;

    // public function offsetUnset(mixed $offset): void;
}