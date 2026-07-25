<?php

namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Drivers\MongoDb\DeleteResult as MongoDBDeleteResult;
use Cobalt\Database\Drivers\MongoDb\InsertOneResult as MongoDBInsertOneResult;
use Cobalt\Database\Drivers\MongoDb\UpdateResult as MongoDBUpdateResult;
use Cobalt\Database\Interfaces\DbClient;
use Cobalt\Database\Interfaces\DbCollection;
use Override;
use Cobalt\Database\Interfaces\InsertOneResult;
use Cobalt\Database\Interfaces\InsertManyResult;
use Cobalt\Database\Interfaces\UpdateResult;
use Cobalt\Database\Interfaces\DeleteResult;
use Cobalt\Database\Classes\CobaltCursor;
use MongoDB\Collection as MongoDBCollection;

class Collection implements DbCollection {
    function __construct(
        readonly Database $database,
        readonly MongoDBCollection $collection
    ) {
        
    }

    #[Override]
    public function getClient(): DbClient {
        return $this->database->client;
    }

    // #[Override]
    // public function setClient(DbClient $client): void {
    //     $this->client = $client;
    // }

    #[Override]
    public function getCollectionName(): string {
        return (string)$this->collection;
    }

    #[Override]
    public function setCollectionName(string $name): void {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function insertOne(array|object $document, array $options = []): InsertOneResult {
        return new MongoDBInsertOneResult($this->collection->insertOne($document, $options));
        
    }

    #[Override]
    public function insertMany(array $documents, array $options = []): InsertManyResult {
        return new InsertManyResults($this->collection->insertMany($documents, $options));
    }

    #[Override]
    public function updateOne(array|object $filter, array|object $fields, array $options = []): UpdateResult {
        return new MongoDBUpdateResult($this->collection->updateOne($filter, $fields, $options));
    }

    #[Override]
    public function updateMany(array|object $filter, array|object $fields, array $options = []): UpdateResult {
        return new MongoDBUpdateResult($this->collection->updateMany($filter, $fields, $options));
    }

    #[Override]
    public function deleteOne(array|object $filter, array $options = []): DeleteResult {
        return new MongoDBDeleteResult($this->collection->deleteOne($filter, $options));
    }

    #[Override]
    public function deleteMany(array|object $filter, array $options = []): DeleteResult {
        return new MongoDBDeleteResult($this->collection->deleteMany($filter, $options));
    }

    /**
     * 
     * @param array $filter 
     * @param array $options 
     * @return array|object|null 
     */
    #[Override]
    public function findOne(array $filter, array $options = []): array|object|null {
        if(isset($options['join'])) {
            $pipeline = [ '$match' => $filter ];
            $options['limit'] = 1;
            self::convertOptionsToPipelineOperations($pipeline, $options);
            $arr = $this->collection->aggregate($pipeline, $options)->toArray();
            return $arr[0] ?? null;
        }
        return $this->collection->findOne($filter, $options);
    }

    #[Override]
    public function findOneAndUpdate(array $filter, array|object $update, array $options = []): null|array|object {
        return $this->findOneAndUpdate($filter, $update, $options);
    }

    /**
     * 
     * @param array &$pipeline 
     * @param array{join:array,sort:array,skip:array,limit:array,projection:array,collation:array,hint:array,comment:string} &$options 
     * @return void 
     */
    static function convertOptionsToPipelineOperations(array &$pipeline, array &$options) {
        if(!empty($options['join'])) {
            $pipeline[] = [ '$lookup' => $options['join'] ];
            unset($options['join']);
        }
            
        if (!empty($options['sort'])) {
            $pipeline[] = [ '$sort' => $options['sort'] ];
        }

        if (!empty($options['skip'])) {
            $pipeline[] = [ '$skip' => $options['skip'] ];
        }

        if (!empty($options['limit'])) {
            $pipeline[] = [ '$limit' => $options['limit'] ];
        }

        if (!empty($options['projection'])) {
            $pipeline[] = [ '$project' => $options['projection'] ];
        }

        // Keep database driver configurations in the $options parameter
        $aggregateOptions = [
            'allowDiskUse' => true // Highly recommended for aggregations
        ];

        if (!empty($options['collation'])) {
            $aggregateOptions['collation'] = $options['collation'];
        }

        if (!empty($options['hint'])) {
            $aggregateOptions['hint'] = $options['hint'];
        }

        if (!empty($options['comment'])) {
            $aggregateOptions['comment'] = $options['comment'];
        }

        $options = $aggregateOptions;
    }

    #[Override]
    public function find(array|object $filter = [], array $options = []): ?CobaltCursor {
        if(isset($options['join'])) {
            $pipeline = [ '$match' => $filter ];
            self::convertOptionsToPipelineOperations($pipeline, $options);
            $arr = $this->collection->aggregate($pipeline, $options);
            if($arr) return new CobaltCursor($arr, [$filter, $options], $this);
            return null;
        }
        return new CobaltCursor($this->collection->find($filter, $options), [$filter, $options], $this);
    }

    #[Override]
    public function count(array|object $filter = [], array $options = []): int {
        return $this->collection->count($filter, $options);
    }

    #[Override]
    public function countDocuments(array|object $filter = [], array $options = []): int {
        return $this->collection->countDocuments($filter, $options);
    }

    #[Override]
    public function distinct(string $fieldName, array $options = []): array {
        return $this->collection->distinct($fieldName, $options);
    }

    #[Override]
    public function createIndex(array|object $key, array $options = []): string {
        return $this->collection->createIndex($key, $options);
    }

    #[Override]
    public static function queryBuilder(array $filter, array $options = []): mixed {
        return [$filter, $options];
    }

    #[Override]
    public static function updateBuilder(array $filter, array $update, array $options): mixed {
        return [$filter, $update, $options];
    }

    #[Override]
    public function aggregate(array $pipeline, array $options = []): CobaltCursor {
        $cursor = new CobaltCursor(
            $this->collection->aggregate($pipeline, $options),
            $pipeline,
        );
        return $cursor;
    }

    public function drop(array $options = []): void {
        $this->collection->drop($options);
    }
}