<?php

namespace Cobalt\Model\Traits;

use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\StringType;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use stdClass;

trait Schemable {
    protected bool $__has_been_unserialized = false;
    protected bool $__has_schema_set = false;
    protected ?ObjectId $_id = null;
    /** @var array $__dataset a set of directives*/
    protected array $__dataset = [];
    protected array $__schema = [];

    abstract protected function define(array &$target, string|int $property, $value, MixedType $instance = null, ?GenericModel $model = null,$name = null):void;

    protected function __defineSchema(array $schema):void {
        // Check if we there's an explicit `defineSchema` function set
        if(method_exists($this, "defineSchema")) {
            $schema = array_merge($this->defineSchema($schema), $schema);
        }
        foreach($schema as $field => $directives) {
            // Let's check if we need to reformat this directive into an array
            if($directives instanceof MixedType) {
                $directives = ['type' => $directives];
            } else if ($directives[0] instanceof MixedType){
                $directives['type'] = $directives[0];
                unset($directives[0]);
            }
            // Define the schema directives
            $this->__schema[$field] = $directives;
            if(!key_exists($field, $this->__dataset)) {
                $this->__dataset[$field] = new $directives['type'];
                $this->__dataset[$field]->setName($field);
                $this->__dataset[$field]->setModel($this);
                $this->__dataset[$field]->setDirectives($directives);
            }
        }
        $this->__has_schema_set = true;
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