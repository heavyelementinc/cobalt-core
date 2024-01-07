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

use MongoDB\BSON\ObjectId;
use Drivers\UTCDateTime;
use MongoDB\Collection;
use Validation\Exceptions\ValidationFailed;
use Validation\Normalize;

abstract class Database {
    public $db = null;
    public $collection;
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
        return $this->collection->insertOne($document, $options);
    }

    final function insertMany($documents, array $options = []) {
        return $this->collection->insertMany($documents, $options);
    }


    /* READ */
    final function findOne($filter, array $options = []) {
        return $this->collection->findOne($filter, $options);
    }

    final function findOneAndUpdate($filter, $update, array $options = []) {
        return $this->collection->findOneAndUpdate($filter, $update, $options);
    }

    final function find($filter = [], array $options = []) {
        return $this->collection->find($filter, $options);
    }

    final function count($filter, $options = []) {
        return $this->collection->count($filter, $options);
    }

    final function distinct($field,$filter = []) {
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
        return $this->collection->updateOne($filter, $fields, $options);
    }

    final function updateMany($filter, $fields, array $options = []) {
        return $this->collection->updateMany($filter, $fields, $options);
    }


    /* DESTROY */
    final function deleteOne($filter, array $options = []) {
        return $this->collection->deleteOne($filter, $options);
    }

    final function deleteMany($filter, array $options = []) {
        return $this->collection->deleteMany($filter, $options);
    }

    final function aggregate($pipeline, $options = []) {
        return $this->collection->aggregate($pipeline, $options);
    }
}
