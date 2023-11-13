<?php

/** Stubs to appease the IDE gods */

namespace MongoDB\BSON;

use MongoDB\BSON\Document;
use MongoDB\BSON\PackedArray;

interface Persistable {
    public function bsonSerialize(): array|\stdClass|Document;

    public function bsonUnserialize(array $data): void;
}