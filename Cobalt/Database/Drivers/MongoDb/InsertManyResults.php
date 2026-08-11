<?php

namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Interfaces\InsertManyResult;
use MongoDB\InsertManyResult as MongoDBInsertManyResult;
use Override;

class InsertManyResults implements InsertManyResult {
    function __construct(readonly MongoDBInsertManyResult $result) {
        
    }
    #[Override]
    public function getInsertedCount(): int {
        return $this->result->getInsertedCount();
    }

    #[Override]
    public function getInsertedIds(): array {
        return $this->result->getInsertedIds();
    }

    #[Override]
    public function isAcknowledged(): bool {
        return $this->result->isAcknowledged();
    }

}