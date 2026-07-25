<?php

namespace Cobalt\Database\Interfaces;

use Cobalt\Database\Classes\CobaltCursor;
use JsonSerializable;
use MongoDB\BSON\Persistable;

interface DbCollection {

    function getClient():DbClient;
    // function setClient(DbClient $client):void;

    function getCollectionName():string;
    function setCollectionName(string $name):void;

    function insertOne(array|object $document, array $options = []):InsertOneResult;
    function insertMany(array $documents, array $options = []):InsertManyResult;

    function updateOne(array|object $filter, array|object $fields, array $options = []):UpdateResult;
    function updateMany(array|object $filter, array|object $fields, array $options = []):UpdateResult;

    function deleteOne(array|object $filter, array $options = []):DeleteResult;
    function deleteMany(array|object $filter, array $options = []):DeleteResult;

    function findOne(array $filter, array $options = []):array|object|null;
    function findOneAndUpdate(array $filter, array|object $update, array $options = []):null|array|object;
    function find(array|object $filter = [], array $options = []):?CobaltCursor;

    function count(array|object $filter = [], array $options =[]):int;
    function countDocuments(array|object $filter = [], array $options = []):int;
    function distinct(string $fieldName, array $options = []):array;

    function createIndex(array|object $key, array $options = []):string;

    static function queryBuilder(array $filter, array $options = []):mixed;
    static function updateBuilder(array $filter, array $update, array $options):mixed;

    public function aggregate(array $pipeline, array $options = []): CobaltCursor;

    public function drop(array $options = []): void;
}