<?php
namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Interfaces\UpdateResult as InterfacesUpdateResult;
use MongoDB\UpdateResult as MongoDBUpdateResult;
use Override;

class UpdateResult implements InterfacesUpdateResult {
    function __construct(readonly MongoDBUpdateResult $result) {

    }
    #[Override]
    public function getMatchedCount(): int {
        return $this->result->getMatchedCount();
    }

    #[Override]
    public function getModifiedCount(): int {
        return $this->result->getModifiedCount();
    }

    #[Override]
    public function getUpsertedCount(): int {
        return $this->result->getUpsertedCount();
    }

    #[Override]
    public function getUpsertedId(): mixed {
        return $this->result->getUpsertedId();
    }

    #[Override]
    public function isAcknowledged(): bool {
        return $this->result->isAcknowledged();
    }
    
}