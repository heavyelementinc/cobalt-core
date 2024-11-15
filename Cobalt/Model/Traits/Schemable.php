<?php

namespace Cobalt\Model\Traits;

use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\StringType;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use stdClass;

trait Schemable {
    protected bool $__has_been_unserialized = false;
    protected ?ObjectId $_id = null;
    /** @var array $__dataset a set of directives*/
    protected array $__dataset = [];
    protected array $__schema = [];

    protected function __defineSchema(array $schema):void {
        // Check if we there's an explicit `defineSchema` function set
        if(method_exists($this, "defineSchema")) {
            $schema = array_merge($this->defineSchema($schema), $schema);
        }
        foreach($schema as $field => $directive) {
            // Check if the field is a `type`
            if($field === 0 && !isset($schema['type'])) {
                $schema['type'] = $directive;
                unset($schema[$field]);
            }
            // Define the schema directives
            $this->__schema[$field] = $schema;
        }
    }

    final protected function define(string $property, $value):void {
        $instance = null;
        // Let's check to see if our schema has defined a 
        if(key_exists($property, $this->__schema) && $this->__schema[$property]['type']){
            if($this->__schema[$property]['type'] instanceof MixedType) $instance = $this->__schema[$property]['type'];
        } 
        
        if($instance === null) {
            $type = gettype($value);
            switch($type) {
                case "string":
                    $instance = new StringType();
                    break;
                case "int":
                    // $this->__dataset[$property] =
                    // break;
                default:
                    $instance = new MixedType();
            }
        }

        $instance->setValue($property);
        $instance->setModel($this);
        $this->__dataset[$property] = $instance;

    }

    public function bsonSerialize(): array|stdClass|Document {
        return $this->__dataset;
    }

    public function bsonUnserialize(array $data): void {
        $this->_id = $data['_id'];
        foreach($data as $index => $value) {
            if($index === "_id") continue;
            $this->__set($index, $value);
        }
        $this->__has_been_unserialized = true;
    }
}