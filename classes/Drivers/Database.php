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
use Validation\Exceptions\ValidationFailed;

abstract class Database {
    public $db = __APP_SETTINGS__['database'];
    private $collection;

    /** @return string the name of the database collection (table) */
    abstract function get_collection_name();

    function __construct($database = null) {
        if ($database !== null) $this->db = $database;
        $this->collection = db_cursor($this->get_collection_name(), $this->db);
    }

    /* HELPERS */
    final function __id($id = null) {
        if ($id === null) return new ObjectId();
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

    final function find($filter = [], array $options = []) {
        return $this->collection->find($filter, $options);
    }

    final function count($filter, $options = []) {
        return $this->collection->count($filter, $options);
    }

    final function distinct($field) {
        return $this->collection->distinct($field);
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
