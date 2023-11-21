<?php

namespace Cobalt\SchemaPrototypes;

use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;

class PersistableResult extends SchemaResult implements Persistable {

    public function bsonSerialize(): array|stdClass|Document {
        return [
            'value' => $this->value
        ];
    }

    public function bsonUnserialize(array $data): void {
        $this->value = $data['value'];
    }
}