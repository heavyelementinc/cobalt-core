<?php
namespace Cobalt\Model\Traits;

use Cobalt\DBManagement\CobaltCursor;
use Error;
use Exception;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\DeleteResult;
use MongoDB\Driver\Cursor;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;
use PDO;

trait Accessible {
    private array $TYPE_MAP = [
        // 'typeMap' => [
        //     'root' => 'array',
        //     'document' => 'array',
        //     'array' => 'array'
        // ]
    ];
    public null|Client|PDO $client = null;
    public ?Database $db;
    public ?Collection $collection;
    public string $collectionSpecifiedAtConstruction;

    private array $query = [];
    

    abstract function getCollectionName($string = null):string;

    /**
     * Initializes the database for any Accessible call.
     * If it's already initialized, this call does nothing.
     * @return void 
     */
    protected function __initAccessible($database = null, $collection = null):void {
        if($this->client) return;
        switch(config()['db_driver']) {
            case DATABASE_DRIVER_MONGODB:
                $this->__initMongoDB($database, $collection);
                break;
            case DATABASE_DRIVER_POSTGRES:
                $this->__initPostgres();
                break;
            default:
                throw new Exception("Driver type `".config()['db_driver']."` is not a recognized or supported driver");
        }
    }
    
    protected function __initMongoDB($database = null, $collection = null) {
        if(!$collection) $collection = $this->getCollectionName();
        $this->client = db_cursor($collection, $database, true);
        $this->db = $this->client->{$database ?? config()['database']};
        $this->collection = $this->db->{$collection};
    }

    protected function __initPostgres($database = null, $table = null) {
        $host = config()['db_addr'];
        $user = config()['db_usr'];
        $pass = config()['db_pwd'];
        $database = config()['database'];

        $dsn = "postgres:host=$host;dbname=$database";
        $this->client = new PDO($dsn, $user, $pass);
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
        $options += $this->TYPE_MAP;
        return $this->collection->findOne($filter, $options);
    }

    final function findOneAndUpdate($filter, $update, array $options = []):array|object|null {
        $this->__initAccessible();
        benchmark_reads();
        $options += $this->TYPE_MAP;
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
        $options += $this->TYPE_MAP;
        if($this->client instanceof Client) {
            $cursor = $this->collection->find($filter, $options);
            if($cursor) return new CobaltCursor($cursor);
            return null;
        } else {
            throw new Exception(config()['db_driver']." is not implemented!");
        }
    }

    final function findAndModify($filter = [], array $options = []) {
        throw new Error("You're probably looking for findOneAndUpdate");
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
        if($this->client instanceof Client) return $this->collection->count($filter, $options);
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
        $options += $this->TYPE_MAP;
        $cursor = $this->collection->aggregate($pipeline, $options);
        benchmark_reads();
        return $cursor;
    }

    final function drop() {
        $this->__initAccessible();
        $this->collection->drop();
    }
}