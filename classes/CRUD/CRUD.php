<?php

namespace CRUD;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use CRUD\Exceptions\ValidationFailed;

abstract class CRUD {
    public $db = __APP_SETTINGS__['database'];
    private $collection;

    /**
     * @return string the name of the database collection (table)
     */
    abstract function get_collection_name();

    function __construct() {
        $this->collection = db_cursor($this->get_collection_name(), $this->db);
    }


    /** HELPERS */
    function __id($id = null) {
        if ($id === null) return new ObjectId();
        return new ObjectId($id);
    }

    function __date($date = null) {
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

    private function date_string_parse($date) {
        $timestamp = strtotime($date);
        if ($timestamp) return new UTCDateTime($date);
        throw new ValidationFailed("Invalid date parameter");
    }


    /** CREATE */
    function insertOne($document, array $options = []) {
        return $this->collection->insertOne($document, $options);
    }

    function insertMany($documents, array $options = []) {
        return $this->collection->insertMany($documents, $options);
    }


    /** READ */
    function findOne($filter, array $options = []) {
        return $this->collection->findOne($filter, $options);
    }

    function find($filter = [], array $options = []) {
        return $this->collection->find($filter, $options);
    }


    /** UPDATE */
    function updateOne($filter, $fields, array $options = []) {
        return $this->collection->updateOne($filter, $fields, $options);
    }

    function updateMany($filter, $fields, array $options = []) {
        return $this->collection->updateMany($filter, $fields, $options);
    }


    /** DESTROY */
    function deleteOne($filter, array $options = []) {
        return $this->collection->deleteOne($filter, $options);
    }

    function deleteMany($filter, array $options = []) {
        return $this->collection->deleteMany($filter, $options);
    }
}