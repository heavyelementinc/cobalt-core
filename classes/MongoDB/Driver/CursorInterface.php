<?php

namespace MongoDB\Driver;

use Iterator;

abstract class CursorInterface implements Iterator {
    /* Methods */
    abstract public function getId(): MongoDB\BSON\Int64;
    abstract public function getServer(): MongoDB\Driver\Server;
    abstract public function isDead(): bool;
    abstract public function setTypeMap(array $typemap): void;
    abstract public function toArray(): array;
}