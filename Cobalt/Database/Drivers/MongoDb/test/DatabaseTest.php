<?php

use Cobalt\Database\Drivers\MongoDb\Client;
use Cobalt\Database\Drivers\MongoDb\Collection;
use Cobalt\Database\Drivers\MongoDb\Database;
use MongoDB\BSON\ObjectId;
use MongoDB\Client as MongoDBClient;
use MongoDB\Database as MongoDBDatabase;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase {
    readonly Client $client;
    readonly Database $db;
    readonly Collection $collection;
    protected $document = [
        'test1' => 'Test 1',
        'test2' => 2
    ];

    private function setup_db() {
        $this->client = new Client();
        $this->db = $this->client->getDatabase('test');
        $this->collection = $this->db->getCollection('test');
    }

    protected ObjectId $id;
    function test__setup() {
        $this->setup_db();

        $this->assertTrue($this->client instanceof Client, 'Must be an instance of Client');
        $this->assertTrue($this->client->client instanceof MongoDBClient, 'Must be an instance of MongoDB client');
        $this->assertTrue($this->db instanceof Database, 'Must be an instance of MongoDb');
        $this->assertTrue($this->client === $this->db->client, 'Must be the same instance of client');
        $this->assertTrue($this->collection->database === $this->db, 'DB instance must match');
    }

    function test_insert() {
        $this->setup_db();

        $result = $this->collection->insertOne(['insertOne' => true, ...$this->document]);
        $this->assertTrue($result->getInsertedCount() === 1, 'Failed to insert data');
        $this->assertTrue($result->getInsertedId() instanceof ObjectId, 'Failed to get ObjectId');
        
        $resultMulti = $this->collection->insertMany(array_fill(0, 5, $this->document));
        $this->assertTrue($resultMulti->getInsertedCount() === 5, 'Failed to insert the correct number of values');
    }

    function test_update() {
        $this->setup_db();
        $result = $this->collection->findOne(['insertOne' => true]);
        $update = $this->collection->updateOne(['_id' => $result['_id']], ['$set' => ['anotherValue' => false]]);
        $this->assertTrue($update->getModifiedCount() === 1, 'Failed to update document');
        // $this->collection->drop();
    }

    function test_delete() {
        $this->setup_db();
        $result = $this->collection->deleteOne(['insertOne' => true]);
        $this->assertTrue($result->getDeletedCount() == 1, "Failed to delete document");
        $delete = $this->collection->deleteMany(['test1' => $this->document['test1']]);
        $this->assertTrue($result->getDeletedCount() >= 1, 'Failed to delete many');
    }
}