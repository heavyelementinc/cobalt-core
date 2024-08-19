<?php


/**
 * Database Driver - Wraps all calls to MongoDB
 * 
 * Meant to provide a way for other developers to build out an SQL backend for 
 * Cobalt Engine since SQL sucks and we currently only support Mongo.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */


namespace Drivers;

use Cobalt\Maps\GenericMap;
use Cobalt\Maps\PersistanceMap;
use Contact\Persistance;
use MongoDB\BSON\ObjectId;
use Drivers\UTCDateTime;
use MongoDB\BSON\Persistable;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Validation\Exceptions\ValidationFailed;
use Validation\Normalize;

abstract class Database {
    public $db = null;
    public Collection $collection;
    public string $__schema;
    public $collectionSpecifiedAtConstruction;

    /** @return string the name of the database collection (table) */
    abstract function get_collection_name();
    
    /**
     * `get_schema_name` is called for every document found. It accepts an
     * optional $doc parameter which can be used to determine an appropriate
     * schema for each document.
     * 
     * @param array $doc 
     * @return string
     */
    function get_schema_name($doc = []) {
        return $this->__schema ?? "\\" . $this::class . "Schema";
    }

    function set_schema($schema) {
        $this->__schema = $schema;
    }

    function __construct($database = null, $collection = null) {
        $this->db = $GLOBALS['CONFIG']['database'];
        if ($database !== null) $this->db = $database;
        if ($collection !== null) $this->collectionSpecifiedAtConstruction = $collection;
        $this->collection = db_cursor($collection ?? $this->get_collection_name(), $this->db);
    }

    /* HELPERS */
    final function __id($id = null) {
        if ($id === null) return new ObjectId();
        if($id instanceof ObjectId) return $id;
        return new ObjectId($id);
    }

    final function __date($value = null) {
        $date = new UTCDateTime($value);
        return $date->timestamp;
    }

    /* CREATE */
    final function insertOne($document, array $options = []) {
        $cursor = $this->collection->insertOne($document, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }

    final function insertMany($documents, array $options = []) {
        $cursor = $this->collection->insertMany($documents, $options);
        benchmark_writes($cursor->getInsertedCount());
        return $cursor;
    }


    /* READ */
    final function findOne($filter, array $options = []) {
        benchmark_reads();
        return $this->collection->findOne($filter, $options);
    }

    final function findOneAndUpdate($filter, $update, array $options = []) {
        benchmark_reads();
        return $this->collection->findOneAndUpdate($filter, $update, $options);
    }

    final function find($filter = [], array $options = []) {
        benchmark_reads();
        return $this->collection->find($filter, $options);
    }

    final function count($filter, $options = []):int {
        benchmark_reads();
        return $this->collection->count($filter, $options);
    }

    final function distinct($field,$filter = []):array {
        benchmark_reads();
        return $this->collection->distinct($field, $filter);
    }

    /**
     * @deprecated 
     * @param mixed $filter 
     * @param array $options 
     * @param mixed $schema 
     * @return object|null 
     */
    function findOneAsSchema($filter, array $options = [], $schema = null) {
        $result = $this->findOne($filter,$options);
        if(!$schema) $schema = $this->get_schema_name($result);
        if($result) return new $schema($result);
        return null;
    }

    /**
     * @deprecated 
     * @param mixed $filter 
     * @param array $options 
     * @param mixed $schema 
     * @param bool $idsAsKeys 
     * @return object[] 
     */
    function findAllAsSchema($filter, array $options = [], $schema = null, $idsAsKeys = false) {
        $results = $this->find($filter, $options);
        $schemaTest = $this->get_schema_name();
        if(is_a(new $schemaTest, "\\Cobalt\\Maps\\GenericMap")) return iterator_to_array($results);
        $processed = [];
        foreach($results as $i => $result) {
            $_schema = $schema ?? $this->get_schema_name($result);
            $key = $i;
            if($idsAsKeys) $key = (string)$result->_id ?? $i;
            $processed[$key] = new $_schema($result);
        }
        return $processed;
    }

    /* UPDATE */
    final function updateOne($filter, $fields, array $options = []) {
        $cursor = $this->collection->updateOne($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }

    final function updateMany($filter, $fields, array $options = []) {
        $cursor = $this->collection->updateMany($filter, $fields, $options);
        benchmark_writes($cursor->getModifiedCount() + $cursor->getUpsertedCount());
        return $cursor;
    }


    /* DESTROY */
    final function deleteOne($filter, array $options = []) {
        $cursor = $this->collection->deleteOne($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final function deleteMany($filter, array $options = []) {
        $cursor = $this->collection->deleteMany($filter, $options);
        benchmark_writes($cursor->getDeletedCount());
        return $cursor;
    }

    final function aggregate($pipeline, $options = []) {
        $cursor = $this->collection->aggregate($pipeline, $options);
        benchmark_reads();
        return $cursor;
    }
}
