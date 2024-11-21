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

trait Defineable {
    protected function define(array &$target, string|int $property, $value, MixedType $instance = null, ?GenericModel $model = null,$name = null):void {
        if($instance == null) $instance = $target[$property] ?? null;
        if($model == null && $instance->model) $model = $instance->model;

        // $instance = $target[$property];
        // Let's check to see if our schema has defined a 
        // if(key_exists($property, $this->__schema) && $this->__schema[$property]['type']){
        //     if($this->__schema[$property]['type'] instanceof MixedType) $instance = $this->__schema[$property]['type'];
        // }
        
        if($instance === null) {
            $type = gettype($value);
            switch($type) {
                case "string":
                    $instance = new StringType();
                    break;
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
            $instance->setName($name ?? $property);
            $instance->setModel($model);
        }

        $instance->setValue($value);
        $target[$property] = $instance;
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
}