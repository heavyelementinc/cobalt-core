<?php

namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Interfaces\DbCollection;
use Cobalt\Database\Interfaces\DbDatabase;
use Cobalt\Database\Drivers\MongoDb\Client;
use Cobalt\Database\Interfaces\DbFilesystem;
use MongoDB\Database as MongoDBDatabase;
use Override;

class Database implements DbDatabase {
    function __construct(readonly Client $client, readonly MongoDBDatabase $database) {
        
    }

    #[Override]
    public function selectFilesystem(array $options = []): DbFilesystem {
        return new Filesystem($this, $this->database->selectGridFSBucket($options));
    }

    #[Override]
    public function dropDatabase(string $database, array $options = []): void {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getCollection(string $collection): DbCollection {
        return new Collection($this, $this->database->getCollection($collection));
    }

    public function drop(array $options = []): void {
        $this->database->drop($options);
    }
}