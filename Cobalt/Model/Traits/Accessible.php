<?php
namespace Cobalt\Model\Traits;

use Exception;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;

trait Accessible {
    public ?Client $client = null;
    public ?Database $db;
    public ?Collection $collection;
    public string $collectionSpecifiedAtConstruction;

    abstract function getCollectionName($string = null):string;

    /**
     * Initializes the database for any Accessible call.
     * If it's already initialized, this call does nothing.
     * @return void 
     */
    protected function __initAccessible($database = null, $collection = null):void {
        if($this->client) return;
        if(!$collection) $collection = $this->getCollectionName();
        $this->client = db_cursor($collection, $database, true);
        $this->db = $this->client->{$database ?? config()['database']};
        $this->collection = $this->db->{$collection};
    }

    /* CREATE */
    final function insertOne($document, array $options = []) {
        $this->__initAccessible();
        $cursor = $this->collection->insertOne($document, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }

    final function insertMany($documents, array $options = []) {
        $this->__initAccessible();
        $cursor = $this->collection->insertMany($documents, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }


    /* READ */
    final function findOne($filter, array $options = []) {
        $this->__initAccessible();
        benchmark_reads();
        return $this->collection->findOne($filter, $options);
    }

    final function findOneAndUpdate($filter, $update, array $options = []) {
        $this->__initAccessible();
        benchmark_reads();
        return $this->collection->findOneAndUpdate($filter, $update, $options);
    }

    final function find($filter = [], array $options = []) {
        $this->__initAccessible();
        benchmark_reads();
        return $this->collection->find($filter, $options);
    }

    /**
     * @deprecated 1.4
     * @param mixed $filter 
     * @param array $options 
     * @return int 
     */
    final function count($filter, $options = []):int {
        $this->__initAccessible();
        benchmark_reads();
        return $this->collection->count($filter, $options);
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
    final function updateOne($filter, $fields, array $options = []) {
        $this->__initAccessible();
        $cursor = $this->collection->updateOne($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }

    final function updateMany($filter, $fields, array $options = []) {
        $this->__initAccessible();
        $cursor = $this->collection->updateMany($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }

    /* DESTROY */
    final function deleteOne($filter, array $options = []) {
        $this->__initAccessible();
        $cursor = $this->collection->deleteOne($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final function deleteMany($filter, array $options = []) {
        $this->__initAccessible();
        $cursor = $this->collection->deleteMany($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final function aggregate($pipeline, $options = []) {
        $this->__initAccessible();
        $cursor = $this->collection->aggregate($pipeline, $options);
        benchmark_reads();
        return $cursor;
    }
}