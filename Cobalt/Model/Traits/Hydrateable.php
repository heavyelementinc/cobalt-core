<?php
namespace Cobalt\Model\Traits;

use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\ObjectType;
use Cobalt\Model\Types\StringType;
use MongoDB\BSON\Document;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

trait Hydrateable {
    /**
     * 
     * @param array &$target This is usually $this->__dataset
     * @param string|int $field_name The name of the field we're calling
     * @param mixed $value The value that field should be set to
     * @param MixedType|null $instance The given instance
     * @param null|GenericModel $model The model to which this field belongs
     * @param mixed $name 
     * @return void 
     */
    protected function hydrate(array &$target, string|int $field_name, $value, MixedType $instance = null, ?GenericModel $model = null, $name = null, ?array $directives = null):void {
        if($instance == null) $instance = $target[$field_name];
        if($model == null && $instance->model) $model = $instance->model;

        // $instance = $target[$field_name];
        // Let's check to see if our schema has defined a 
        // if(key_exists($field_name, $this->__schema) && $this->__schema[$field_name]['type']){
        //     if($this->__schema[$field_name]['type'] instanceof MixedType) $instance = $this->__schema[$field_name]['type'];
        // }
        
        if($instance === null) {
            $this->implicit_cast($field_name, $value, $target);
            $target[$field_name]->setName($name ?? $field_name);
            $target[$field_name]->setModel($model);
        }
        $target[$field_name]->setValue($value);
        //  = $instance;
    }

    function normalizeMongoDocuments(&$value, $instance = null) {
        if($value instanceof Document) {
            $instance = new ModelType();
        }
        if($value instanceof BSONArray) {
            $instance = new ArrayType();
            $value = $value->getArrayCopy();
        }
        if($value instanceof BSONDocument) {
            $instance = new ModelType();
            $value = $value->getArrayCopy();
        }
        if($instance === null) {
            $instance = new MixedType();
        }
        return $instance;
    }

    function implicit_cast(string $field, mixed $value, array &$target):void {
        $type = gettype($value);
        switch($type) {
            case "string":
                $instance = new StringType();
                break;
            case "integer":
            case "int":
            case "float":
            case "double":
                $instance = new NumberType();
                break;
            case "array":
                if(is_associative_array($value)) {
                    $instance = new ModelType();
                } else $instance = new ArrayType();
                break;
            case "object":
                $instance = $this->normalizeMongoDocuments($value);
                break;
            default:
                $instance = new MixedType();
        }

        $target[$field] = $instance;
    }
}