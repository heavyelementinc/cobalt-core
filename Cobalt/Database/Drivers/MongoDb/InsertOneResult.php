<?php

namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Interfaces\InsertOneResult as InterfacesInsertOneResult;
use MongoDB\InsertOneResult as MongoDBInsertOneResult;
use Override;

class InsertOneResult implements InterfacesInsertOneResult {
    function __construct(readonly MongoDBInsertOneResult $result) {
        
    }
    #[Override]
    public function getInsertedCount(): int {
        return $this->result->getInsertedCount();
    }

    #[Override]
    public function getInsertedId(): mixed {
        return $this->result->getInsertedId();
    }

    #[Override]
    public function isAcknowledged(): bool {
        return $this->result->isAcknowledged();
    }

}