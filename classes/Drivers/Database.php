<?php

namespace Drivers;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Validation\Exceptions\ValidationFailed;

abstract class Database {
    public $db = __APP_SETTINGS__['database'];
    private $collection;

    /** @return string the name of the database collection (table) */
    abstract function get_collection_name();

    function __construct() {
        $this->collection = db_cursor($this->get_collection_name(), $this->db);
    }

    /* HELPERS */
    final function __id($id = null) {
        if ($id === null) return new ObjectId();
        return new ObjectId($id);
    }

    final function __date($date = null) {
        if ($date === null) return new UTCDateTime();
        switch (gettype($date)) {
            case "string":
                return $this->date_string_parse($date);
                break;
            case "integer":
            case "double":
                return new UTCDateTime($date);
                break;
            case "object":
                if ($date instanceof UTCDateTime) return $date;
                break;
        }
        throw new ValidationFailed("Invalid date parameter");
    }

    final function date_string_parse($date) {
        $timestamp = strtotime($date);
        if ($timestamp) return new UTCDateTime($timestamp * 1000);
        throw new ValidationFailed("Invalid date parameter");
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
}
