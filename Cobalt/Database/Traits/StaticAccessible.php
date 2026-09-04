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

trait StaticAccessible {

    public static DbClient $client;
    public static DbDatabase $db;
    public static DbCollection $collection;
    public static string $collectionSpecifiedAtConstruction;

    private static array $query = [];

    abstract static function getCollectionName($string = null):string;

    /**
     * Initializes the database for any Accessible call.
     * If it's already initialized, this call does nothing.
     * @return void 
     */
    protected static function __initAccessible($database = null, $collection = null):void {
        if(isset(static::$client)) return;
        static::$client = getDatabaseClient();
        static::$db = static::$client->getDatabase($database ?? config()['database']);
        static::$collection = static::$db->getCollection($collection ?? static::getCollectionName());
    }

    /* CREATE */
    final static function insertOne($document, array $options = []):InsertOneResult {
        static::__initAccessible();
        $cursor = static::$collection->insertOne($document, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }

    final static function insertMany($documents, array $options = []):InsertManyResult {
        static::__initAccessible();
        $cursor = static::$collection->insertMany($documents, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }

    static function queryBuilder($filter, array $options = []) {
        $table = static::getCollectionName();
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
    /**
     * @return ?static
     */
    final static function findOne($filter, array $options = []):array|object|null {
        static::__initAccessible();
        benchmark_reads();
        $options += static::getTypeMap();
        return static::$collection->findOne($filter, $options);
    }

    /**
     * @return ?static
     */
    final static function findOneAndUpdate($filter, $update, array $options = []):array|object|null {
        static::__initAccessible();
        benchmark_reads();
        $options += static::getTypeMap();
        return static::$collection->findOneAndUpdate($filter, $update, $options);
    }

    /**
     * 
     * @param array $filter 
     * @param array $options
     * @template T of static 
     * @return null|CobaltCursor<T>
     * @throws Exception 
     */
    final static function find($filter = [], array $options = []):?CobaltCursor {
        static::__initAccessible();
        benchmark_reads();
        $options += static::getTypeMap();
        if(static::$client instanceof DbClient) {
            $cursor = static::$collection->find($filter, $options);
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
    // final static function count($filter = [], $options = []):int {
    //     static::__initAccessible();
    //     benchmark_reads();
    //     if(static::$client instanceof DbClient) return static::$collection->count($filter, $options);
    //     return 0;
    // }

    final static function countDocuments($filter = [], $options = []):int {
        static::__initAccessible();
        benchmark_reads();
        return static::$collection->countDocuments($filter, $options);
    }

    final static function distinct(string $field, $filter = [], $options = []):array {
        static::__initAccessible();
        benchmark_reads();
        return static::$collection->distinct($field, $filter, $options);
    }

    final static function createIndex(array|object $key, array $options = []):string {
        static::__initAccessible();
        return static::$collection->createIndex($key, $options);
    }

    /* UPDATE */
    final static function updateOne($filter, $fields, array $options = []):UpdateResult {
        static::__initAccessible();
        $cursor = static::$collection->updateOne($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }

    final static function updateMany($filter, $fields, array $options = []):UpdateResult {
        static::__initAccessible();
        $cursor = static::$collection->updateMany($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }

    /* DESTROY */
    final static function deleteOne($filter, array $options = []):DeleteResult {
        static::__initAccessible();
        $cursor = static::$collection->deleteOne($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final static function deleteMany($filter, array $options = []):DeleteResult {
        static::__initAccessible();
        $cursor = static::$collection->deleteMany($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final static function aggregate($pipeline, $options = []) {
        static::__initAccessible();
        $options += static::getTypeMap();
        $cursor = static::$collection->aggregate($pipeline, $options);
        benchmark_reads();
        return $cursor;
    }

    final static function drop() {
        static::__initAccessible();
        static::$collection->drop();
    }

    /**
     * @return array{'typeMap':array{'root':'array','document':'array','array':'array'}}
     */
    static function getTypeMap():array {
        return [
            'typeMap' => [
                // 'root' => is_a(static::class, Persistable::class, true) ? static::class : 'array',
                'document' => 'array',
                'array' => 'array',
            ]
        ];
    }
}