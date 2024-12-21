<?php

namespace Cobalt\Model\Traits;

use Cobalt\Model\Attributes\DoNotSet;
use Cobalt\Model\Exceptions\DirectiveDefinitionFailure;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\StringType;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONArray;
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

    protected bool $__index_checkbox_state = false;

    abstract function implicit_cast(string $field, mixed $value): MixedType;

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
                $instance = $directives['type'] ?? new MixedType();
                // $this->__dataset[$field]->setName($field);
                // $instance->setModel($this);
                // $instance->setDirectives($directives);
                // $this->__dataset[$field]->setName(($this->name_prefix) ? $this->name_prefix . ".$field" : $field);
                $this->hydrate(
                    target: $this->__dataset,
                    field_name: $field,
                    value: new DoNotSet(),
                    model: $this,
                    name: ($this->name_prefix) ? $this->name_prefix . ".$field" : $field,
                    directives: $directives
                );
            }
        }
        $this->__has_schema_set = true;
    }

    public function __get_index_checkbox_state():bool {
        return $this->__index_checkbox_state;
    }

    public function __set_index_checkbox_state($value) {
        $this->__index_checkbox_state = $value;
    }

    public function readSchema() {
        return $this->__schema;
    }

    public function getData(): array|stdClass|Document {
        $data = [];
        if($this->__include_id) $data['_id'] = $this->_id;
        // // foreach($this->__dataset as $field => $v) {
        // //     $data[$field] = $v->serialize();
        // // }
        // return $data;
        return array_merge($data, $this->serialize());
    }

    public function setData(array|BSONDocument|BSONArray $data): void {
        $this->_id = $data['_id'];
        foreach($data as $index => $value) {
            if($index === "_id") continue;
            $this->__set($index, $value);
        }
        // $this->__set('__version', $this::__getVersion());
        $this->__has_been_unserialized = true;
    }

    public function includeId(bool $state) {
        $this->__include_id = $state;
    }
}