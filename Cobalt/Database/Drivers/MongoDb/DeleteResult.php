<?php

namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Interfaces\DeleteResult as InterfacesDeleteResult;
use MongoDB\DeleteResult as MongoDBDeleteResult;
use Override;

class DeleteResult implements InterfacesDeleteResult {
    function __construct(readonly MongoDBDeleteResult $result) {
        
    }
    
    #[Override]
    public function getDeletedCount(): int {
        return $this->result->getDeletedCount();
    }

    #[Override]
    public function isAcknowledged(): bool {
        return $this->result->isAcknowledged();
    }

}