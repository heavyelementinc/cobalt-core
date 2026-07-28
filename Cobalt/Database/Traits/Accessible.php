<?php

namespace Cobalt\Database\Traits;

use Cobalt\Database\Classes\CobaltCursor;
use Cobalt\Database\Drivers\MongoDb\Client;
use Cobalt\Database\Interfaces\DbClient;
use Cobalt\Database\Interfaces\DbCollection;
use Cobalt\Database\Interfaces\DbDatabase;
use Cobalt\Database\Interfaces\DeleteResult;
use Cobalt\Database\Interfaces\InsertManyResult;
use Cobalt\Database\Interfaces\InsertOneResult;
use Cobalt\Database\Interfaces\UpdateResult;
use Error;
use Exception;
use MongoDB\BSON\Persistable;

trait Accessible {

    public DbClient $client;
    public DbDatabase $db;
    public DbCollection $collection;
    public string $collectionSpecifiedAtConstruction;

    private array $query = [];
    

    abstract function getCollectionName($string = null):string;

    /**
     * Initializes the database for any Accessible call.
     * If it's already initialized, this call does nothing.
     * @return void 
     */
    protected function __initAccessible($database = null, $collection = null):void {
        if(isset($this->client)) return;
        $this->client = getDatabaseClient();
        $this->db = $this->client->getDatabase($database ?? config()['database']);
        $this->collection = $this->db->getCollection($collection ?? $this->getCollectionName());
    }

    /* CREATE */
    final function insertOne($document, array $options = []):InsertOneResult {
        $this->__initAccessible();
        $cursor = $this->collection->insertOne($document, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }

    final function insertMany($documents, array $options = []):InsertManyResult {
        $this->__initAccessible();
        $cursor = $this->collection->insertMany($documents, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }

    function queryBuilder($filter, array $options = []) {
        $table = $this->getCollectionName();
        $transformed_filters = [];
        foreach($filter as $field => $value) {
            $transformed_filters[] = "$field = :$field";
        }
        
        $transformed_options = [];
        foreach($options as $field => $value) {
            $transformed_options[] = "";
        }
        
        $sql = "SELECT * FROM $table WHERE ".implode(" && ",$transformed_filters);
    }

    /* READ */
    final function findOne($filter, array $options = []):array|object|null {
        $this->__initAccessible();
        benchmark_reads();
        $options += $this->getTypeMap();
        return $this->collection->findOne($filter, $options);
    }

    final function findOneAndUpdate($filter, $update, array $options = []):array|object|null {
        $this->__initAccessible();
        benchmark_reads();
        $options += $this->getTypeMap();
        return $this->collection->findOneAndUpdate($filter, $update, $options);
    }

    /**
     * 
     * @param array $filter 
     * @param array $options 
     * @return null|CobaltCursor
     * @throws Exception 
     */
    final function find($filter = [], array $options = []):?CobaltCursor {
        $this->__initAccessible();
        benchmark_reads();
        $options += $this->getTypeMap();
        if($this->client instanceof DbClient) {
            $cursor = $this->collection->find($filter, $options);
            if($cursor) return $cursor;
            return null;
        } else {
            throw new Exception(config()['db_driver']." is not implemented!");
        }
    }

    /**
     * @deprecated 1.4
     * @param mixed $filter 
     * @param array $options 
     * @return int 
     */
    final function count($filter = [], $options = []):int {
        $this->__initAccessible();
        benchmark_reads();
        if($this->client instanceof DbClient) return $this->collection->count($filter, $options);
        return 0;
    }

    final function countDocuments($filter, $options = []):int {
        $this->__initAccessible();
        benchmark_reads();
        return $this->collection->countDocuments($filter, $options);
    }

    final function distinct($field, $filter = [], $options = []):array {
        $this->__initAccessible();
        benchmark_reads();
        return $this->collection->distinct($field, $filter, $options);
    }

    final function createIndex(array|object $key, array $options = []):string {
        $this->__initAccessible();
        return $this->collection->createIndex($key, $options);
    }

    /* UPDATE */
    final function updateOne($filter, $fields, array $options = []):UpdateResult {
        $this->__initAccessible();
        $cursor = $this->collection->updateOne($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }

    final function updateMany($filter, $fields, array $options = []):UpdateResult {
        $this->__initAccessible();
        $cursor = $this->collection->updateMany($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }

    /* DESTROY */
    final function deleteOne($filter, array $options = []):DeleteResult {
        $this->__initAccessible();
        $cursor = $this->collection->deleteOne($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final function deleteMany($filter, array $options = []):DeleteResult {
        $this->__initAccessible();
        $cursor = $this->collection->deleteMany($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final function aggregate($pipeline, $options = []) {
        $this->__initAccessible();
        $options += $this->getTypeMap();
        $cursor = $this->collection->aggregate($pipeline, $options);
        benchmark_reads();
        return $cursor;
    }

    final function drop() {
        $this->__initAccessible();
        $this->collection->drop();
    }

    /**
     * @return array{'typeMap':array{'root':'array','document':'array','array':'array'}}
     */
    function getTypeMap():array {
        return [
            'root' => ($this instanceof Persistable) ? $this::class : 'array',
            'document' => 'array',
            'array' => 'array',
        ];
    }
}