<?php

namespace Cobalt\JobQueue\Interfaces;

use JsonSerializable;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;
use Override;

class BatchItem implements Persistable {
    function __construct(
        public string $field,
        public string $method,
        public array $arguments
    ) {
        
    }

    #[Override]
    public function bsonSerialize(): array|stdClass|Document {
        return [
            'field' => $this->field,
            'method' => $this->method,
            'arguments' => $this->arguments,
        ];
    }

    #[Override]
    public function bsonUnserialize(array $data): void {
        $this->field = $data['field'] ?? "";
        $this->method = $data['method'] ?? "";
        $this->arguments = $data['arguments'] ?? [];
    }

}