<?php

namespace Cobalt\DefinedModel;

use ArrayAccess;
use Cobalt\DefinedModel\Exceptions\GenericModelInitError;
use Cobalt\DefinedModel\Traits\Controller;
use Cobalt\DefinedModel\Traits\ModelGetSet;
use Cobalt\DefinedModel\Traits\ModelInitialize;
use Cobalt\DefinedModel\Traits\ModelUpdate;
use Cobalt\Model\Types\Interfaces\IMixedType;
use Cobalt\Model\Interfaces\ModelInterface;
use Cobalt\Model\Traits\Accessible;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\StringType;
use Exception;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONDocument;
use ReflectionClass;
use stdClass;
use TypeError;

class GenericModel implements ModelInterface, Iterator, ArrayAccess, JsonSerializable, IMixedType {
    use ModelInitialize;
    
    readonly GenericModel $rootModel;
    readonly GenericModel $parentModel;
    public ObjectId $_id;
    public StringType $version;
    private string $name;
    private array|BSONDocument $rawDataset;
    private bool $set = false;

    function __construct() {
        if(!in_array('Cobalt\DefinedModel\Traits\ModelInitialize',class_uses($this))) {
            // throw new GenericModelInitError("This class (".$this::class.") must use the trait Cobalt\DefinedModel\Traits\ModelInitialize");
        }
        $this->iteratorEnumerate();
    }

    private ReflectionClass $reflectionClass;
    private function iteratorEnumerate() {
        // if(method_exists($this, "initializeField") == false) throw new Exception("You must specify the initializeField method");
        $reflectionClass = new ReflectionClass($this);
        $properties = $reflectionClass->getProperties();
        $filteredNames = ['rootModel', 'parentModel'];
        foreach($properties as $prop) {
            $typeName = $prop->getType()?->getName();
            if(in_array($prop->getName(), $filteredNames)) continue;
            // Filter out any built-in types
            switch($typeName) {
                case "boolean":
                case "integer":
                case "double":
                case "string":
                case "array":
                case "object":
                case "resource":
                case "resource (closed)":
                case "mixed":
                case "null":
                    continue 2;
            }
            if(!$typeName) continue;
            if(is_a($typeName, "Cobalt\\Model\\Types\\Interfaces\\IMixedType", true) == false) continue;
            $type = new $typeName();
            $name = $prop->getName();
            $this->iterator_index[] = $name;
            $type->setName($name);
            $type->setParentModel($this);
            $type->setRootModel((isset($this->rootModel)) ? $this->rootModel : $this);
            $this->initializeField($name, $type);
        }
        $this->defineSchema([]);
    }

    function defineSchema(array $schema = []): array {
        return [];
    }

    // ================================================ //
    public function bsonSerialize(): array|stdClass|Document {
        return $this->modelSerialize();
    }

    public function bsonUnserialize(array $data): void {
        $this->modelUnserialize($data);
    }
    
    public function jsonSerialize(): mixed {
        return $this->bsonSerialize();
    }

    // ================================================ //
    private array $iterator_index = [];
    private int $current_index = 0;
    public function current(): mixed {
        return $this->{$this->key()};
    }

    public function next(): void {
        $this->current_index += 1;
    }

    public function key(): mixed {
        return $this->iterator_index[$this->current_index];
    }

    public function valid(): bool {
        return key_exists($this->current_index, $this->iterator_index);
    }

    public function rewind(): void {
        $this->current_index = 0;
    }
    
    // ================================================ //
    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->iterator_index);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->{$offset}->setValue($value);
    }

    public function offsetUnset(mixed $offset): void {
        $this->{$offset}->unset();
    }

    // ================================================ //
    public function modelSerialize():array|stdClass|Document {
        $serializedArray = [];
        foreach($this as $field => $mixedValue) {
            $serializedArray[$field] = $mixedValue->value;
        }
        return $serializedArray;
    }

    public function modelUnserialize(array|BSONDocument $data) {
        $this->rawDataset = $data;
        // We are looping over the field
        foreach($this as $field => $value) {
            // If the field is not in the $data, let's skip the item
            if(!isset($data[$field])) continue;
            // Special case for our _id field since it's an ObjectId
            if($field === "_id") {
                $this->_id = $value;
                continue;
            }
            // Let's set the $field to have a value
            $this->{$field}->setValue($value);
        }
        $this->set = true;
    }

    // ================================================ //
    public function setParentModel(GenericModel $model) {
        $this->parentModel = $model;
    }
    
    public function getParentModel():GenericModel {
        return $this->parentModel;
    }

    public function setRootModel(GenericModel $model) {
        $this->rootModel = $model;
    }

    public function getRootModel():GenericModel {
        return $this->rootModel;
    }

    public function setName(string $value): void {
        $this->name = $value;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setValue(mixed $value): void {
        if(is_array($value) || $value instanceof BSONDocument) {
            $this->modelUnserialize($value);
        }
        $type = gettype($value);
        $err = "Models cannot be set to an instanece of %s";
        switch($type) {
            case "object":
                throw new TypeError(sprintf($err,$value::class));
                break;
            default:
                throw new TypeError(sprintf($err,$type));
        }
    }

    public function getValue(): mixed {
        return $this->modelSerialize();
    }

    public function isSet(): bool {
        return $this->set;
    }

    public function finalInitialization(): void {

    }
}