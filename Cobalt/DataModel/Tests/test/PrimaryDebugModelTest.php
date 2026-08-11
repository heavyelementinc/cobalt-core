<?php

use Cobalt\DataModel\Tests\PrimaryDebugModel;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class PrimaryDebugModelTest extends TestCase {
    public array $fields = [
        'array' => ['one','two'],
        'bool' => false,
        'foreign' => null,
        'number' => 2,
        'string' => 'Some string',
        'valid' => 'test',
    ];

    public function testTestInstantiationAndSerialization() {
        $this->fields['foreign'] = new ObjectId();
        $model = new PrimaryDebugModel();
        
        // Test idempotency
        $model->bsonUnserialize($this->fields);
        foreach($model as $key => $val) {
            $this->assertTrue($this->fields[$key] == $val->serialize(), "Failed to assign field `$key`");
        }
        /** @var array $serialized */
        $serialized = $model->bsonSerialize();
        $key_intersection = array_diff(array_keys($this->fields), array_keys($serialized));
        $this->assertTrue(empty($key_intersection), 'Computed key intersection contained too many keys');
        $this->assertTrue($this->fields === $serialized, '$this->fields should exactly equal $serialized');
    }
    
    // public function testFieldAssignments() {
    //     $this->fields['foreign'] = new ObjectId();
    // }

    // public function test
}