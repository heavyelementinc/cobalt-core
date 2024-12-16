<?php

namespace Cobalt\Model\Traits;

use Cobalt\Model\Exceptions\DirectiveDefinitionFailure;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\StringType;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use stdClass;

trait Schemable {
    protected bool $__has_been_unserialized = false;
    protected bool $__has_schema_set = false;
    protected $_id = null;
    protected $__include_id = false;
    /** @var array $__dataset a set of directives*/
    protected array $__dataset = [];
    protected array $__schema = [];

    abstract function implicit_cast(string $field, mixed $value, array &$target):void;

    protected function __defineSchema(array $schema):void {
        // We don't need this because the Model class will execute defineSchema 
        // and pass the value to the constructor
        // if(method_exists($this, "defineSchema")) {
        // }
        $schema = array_merge($this->__schema, $schema);

        foreach($schema as $field => $directives) {
            // Let's check if we need to reformat this directive into an array
            if ($directives[0] instanceof MixedType){
                // If the first directive entry is an instance of Mixed Type
                $directives['type'] = $directives[0];
                unset($directives[0]);
            } else if($directives instanceof MixedType) {
                $directives = ['type' => $directives];
            }

            if(!isset($directives['type'])) throw new DirectiveDefinitionFailure("Field `$field` lacks a declared 'type' directive");
            
            // Define the schema directives
            $this->__schema[$field] = $directives;
            if(!key_exists($field, $this->__dataset)) {
                $this->__dataset[$field] = $directives['type'] ?? new MixedType();
                $this->__dataset[$field]->setName($field);
                $this->__dataset[$field]->setModel($this);
                $this->__dataset[$field]->setDirectives($directives);
            }
        }
        $this->__has_schema_set = true;
    }

    public function getData(): array|stdClass|Document {
        $data = [];
        if($this->__include_id) $data['_id'] = $this->_id;
        foreach($this->__dataset as $field => $v) {
            $data[$field] = $v->serialize();
        }
        return $data;
    }

    public function setData(array|BSONDocument $data): void {
        $this->_id = $data['_id'];
        foreach($data as $index => $value) {
            if($index === "_id") continue;
            $this->__set($index, $value);
        }
        $this->__has_been_unserialized = true;
    }

    public function includeId(bool $state) {
        $this->__include_id = $state;
    }
}