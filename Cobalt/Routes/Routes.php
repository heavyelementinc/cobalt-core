<?php

namespace Routes;

use ArrayAccess;
use Iterator;

class Routes implements ArrayAccess, Iterator {
    private int $index = 0;
    private array $routes = [];

    public function current(): mixed { }

    public function next(): void { }

    public function key(): mixed { }

    public function valid(): bool { }

    public function rewind(): void { }

    public function offsetExists(mixed $offset): bool {
        
    }

    public function offsetGet(mixed $offset): mixed { }

    public function offsetSet(mixed $offset, mixed $value): void { }

    public function offsetUnset(mixed $offset): void { }

}