<?php

namespace Cobalt\DataModel\Types;

use ArrayAccess;
use Cobalt\DataModel\Classes\DirectiveList;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Directives\DefaultValue;
use Cobalt\DataModel\Filters\FilterFailed;
use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Filters\FilterResult;
use Cobalt\DataModel\Traits\Overloading;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\StringType;
use Countable;
use Dom\DocumentType;
use Iterator;
use JsonSerializable;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use TypeError;

class DictionaryType extends Generic implements Iterator, Countable, ArrayAccess, JsonSerializable {
    use Overloading;
    protected bool $__isInitialized = false;
    protected bool $__isConstructed = false;
    protected bool $__allowOverloadedFilterFields = false;

    function __construct(null|DictionaryType|ArrayType $model = null, ?DictionaryType $rootModel = null) {
        $this->__isConstructed = true;
        // Set our default model and rootModel to our current instance.
        // If we're a child of another model, it will get overwritten.
        parent::__construct($model, $rootModel);
        $this->initialize();
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        // $issueCapture = new FilterFailed("Failed to validate fields");
        foreach($toValidate as $fieldName => $value) {
            if(!isset($this->{$fieldName})) {
                $this->{$fieldName} = $value;
                if($this->__allowOverloadedFilterFields === false) {
                    // $issueCapture->addFailedField($this->{$fieldName}, new FilterIssue("`$fieldName` is not recognized in this model."));
                    $this->filterResult->addIssue($this, "`$fieldName` is not recognized in this model.");
                    continue;
                }
            }
            /** @var FilterResult $filterResult */
            $this->{$fieldName}->__filter($value);

            // if($filterResult->hasIssues() === false) continue;
            
            // If this filter process raised issues, let's raise the issue:
            // foreach($filterResult->getIssues() as $filterIssue) {
            //     if($filterIssue instanceof FilterFailed) {
            //         $issueCapture->unwrap($filterIssue);
            //         continue;
            //     }
            //     $issueCapture->addFailedField($this->{$fieldName}, $filterIssue);
            // }
        }
        // This line throws a new FilterFailed issue up to the __filter function
        // and it handles returning the result to the value
        // if($issueCapture->count() !== 0) throw $issueCapture;
        return $this->getModifiedFields();
    }

    function getModifiedFields() {
        $result = [];
        foreach($this as $field => $value) {
            if($value->isModified()) {
                $result[$field] = $value->serialize(self::SERIALIZE_MODE_ONLY_MODIFIED);
            }
        }
        return $result;
    }

    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        // Check if this document has been modified and, if it has, only return 
        // the modified fields
        if($this->isModified()) return $this->getModifiedFields();
        $arr = [];
        /** @var string $field
         * @var Generic $value
         */
        foreach($this as $field => $value) {
            if($mode & self::SERIALIZE_MODE_ONLY_PUBLIC && $value->directives?->private?->getValue()) continue;
            $arr[$field] = $value->toClientJson($mode);
        }
        return $arr;
    }

    public function jsonSerialize(): mixed {
        return $this->toClientJson();
    }

    #[Override]
    public function toClientJson(?int $mode = null):mixed {
        if(!$mode) $mode = self::SERIALIZE_MODE_ONLY_PUBLIC + self::SERIALIZE_MODE_VALUE_DISPLAY + self::SERIALIZE_MODE_INCLUDE_FOREIGN_FIELDS + self::SERIALIZE_MODE_INCLUDE_ID;
        $serialized = [];
        $serialized = $this->serialize($mode);
        if(isset($this->_id) && $mode & self::SERIALIZE_MODE_INCLUDE_ID) {
            $serialized['_id'] = (string)$this->_id;
        }
        return $serialized;
    }

    #[Override]
    public function setValue($mixed):void {
        if($this->__isInitialized == false) $this->__construct();
        /** @var Generic $value */
        foreach($mixed as $field => $value) {
            if(!isset($this->{$field})) {
                $this->{$field} = $value;
                continue;
            }
            $this->{$field}->setValue($value);
        }
    }

    #[Override]
    public function updateValue(mixed $value) {
        foreach($value as $name => $val) {
            $this->{$name}->updateValue($val);
        }
        $this->__isModified = true;
    }
    
    public function initialize() {
        if($this->__isConstructed === false) $this->__construct();
        if($this->__isInitialized === true) return;
        $this->__beforeInitialized();
        $class = new ReflectionClass($this);

        $classAttributes = $class->getAttributes();
        $this->handleAttributes($classAttributes, $this->directives);

        // Handle schema properties
        $properties = $class->getProperties();

        /** @var ReflectionProperty $property */
        foreach($properties as $property) {
            $this->modelFieldHandler($property);
        }

        $this->__isInitialized = true;
        $this->__onInitialized();
    }

    public function __beforeInitialized() {}
    public function __onInitialized() {}

    protected function modelFieldHandler(ReflectionProperty $property) {
        $type = $property->getType();
        if($type instanceof ReflectionNamedType == false) return false;
        
        $typeName = $type->getName();
        $generic = null;
        if($this->isValidModelField($property, $typeName, $generic) == false) return false;
        
        /** @var Generic $generic */
        $name = $property->getName();
        $this->{$name} = $generic;
        
        // Set up our properties
        $generic->setName($name, $this->composeFieldname($name));

        $attributes = $property->getAttributes();
        self::handleAttributes($attributes, $generic->directives);
        
        array_push($this->_fields, $name);
        return true;
    }

    protected function composeFieldname(string|int $name):string {
        return (isset($this->fieldname)) ? "$this->fieldname.$name" : $name;
    }

    protected function isValidModelField(ReflectionProperty $property, string $typeName, mixed &$generic):false|Generic {
        // Model fields must be public
        if(!$property->isPublic()) return false;
        // Model fields must be explicitly `readonly`
        if(!$property->isReadOnly()) return false;
        
        // Filter out non-Generic `public readonly` fields
        if(!is_a($property->getType()->getName(), Generic::class, true)) return false;

        // Do we need to double-check that this is a generic?
        // Model fields must be an instance of `Generic`
        /** @var Generic $instance */
        $generic = new $typeName($this, $this->rootModel);
        if($generic instanceof Generic === false) return false;
        return $generic;
    }


    

    #[Override]
    public function offsetExists(mixed $offset): bool {
        return isset($this->{$offset});
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed {
        return $this->{$offset}?->value;
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        if(!isset($this->{$offset})) throw new TypeError("$offset is not accessible");
        if($this->{$offset} instanceof Generic == false) throw new TypeError("$offset is not a Generic!");
        $this->{$offset}->value = $value;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void {
        unset($this->{$offset});
    }


    
    #[Override]
    public function count(): int {
        return count($this->_fields);
    }
    
    #[Override]
    public function current(): mixed {
        return $this->{$this->key()};
    }

    #[Override]
    public function next(): void {
        $this->_fields_iterator_index += 1;
    }

    #[Override]
    public function key(): mixed {
        return $this->_fields[$this->_fields_iterator_index];
    }

    #[Override]
    public function valid(): bool {
        return (count($this->_fields) - 1) >= $this->_fields_iterator_index;
    }

    #[Override]
    public function rewind(): void {
        $this->_fields_iterator_index = 0;
    }

}