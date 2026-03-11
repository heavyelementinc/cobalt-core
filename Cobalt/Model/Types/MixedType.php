<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\DefinedModel\DefinedModel;
use Cobalt\DefinedModel\GenericModel as NewGenericModel;
use Cobalt\Model\Exceptions\ImmutableTypeError;
use Cobalt\Model\Exceptions\Undefined;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\Interfaces\IMixedType;
use Cobalt\Model\Types\Traits\DirectiveBaseline;
use Cobalt\Model\Types\Traits\ClientUpdateFilter;
use Cobalt\Model\Types\Traits\MixedTypeToField;
use Cobalt\Model\Types\Traits\Prototypable;
use Cobalt\Model\Types\Traits\SharedFilterEnums;
use MongoDB\BSON\Document;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Stringable;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationSkip;

class MixedType implements Stringable, ArrayAccess, IMixedType {
    const DEFAULT = DIRECTIVE_KEY_DEFAULT;
    const IMMUTABLE = DIRECTIVE_KEY_IMMUTABLE;
    const VALID = DIRECTIVE_KEY_VALID;
    const SKIP_VALIDATION = DIRECTIVE_KEY_SKIP_VALIDATION;
    const FILTER = DIRECTIVE_KEY_FILTER;
    const TYPECAST = DIRECTIVE_KEY_TYPECAST;
    const GET = DIRECTIVE_KEY_GET;
    const SET = DIRECTIVE_KEY_SET;

    use Prototypable, ClientUpdateFilter, DirectiveBaseline, MixedTypeToField, SharedFilterEnums;
    protected bool $isSet = false;
    protected $value = null;
    protected string $type = "mixed";
    // protected string $name;
    protected string $fieldName = "";
    protected bool $hasModel = false;
    protected GenericModel|NewGenericModel $model;
    protected NewGenericModel $rootModel;
    protected NewGenericModel $parentModel;

    public function setParentModel(NewGenericModel $model) {
        $this->parentModel = $model;
        $this->model = $model;
    }

    public function getParentModel(): NewGenericModel {
        return $this->parentModel;
    }

    public function setRootModel(NewGenericModel $model) {
        $this->rootModel = $model;
    }

    public function getRootModel(): NewGenericModel {
        return $this->rootModel;
    }

    public function isSet(): bool {
        return $this->isSet;
    }

    /**
     * The getValue() function will return the present value or the 
     * 'default' directive if it's not set. If no default is set, null
     * is returned
     * @return void|mixed 
     */
    public function getValue():mixed {
        $val = $this->value;
        if(!$this->isSet) $val = $this->directiveOrNull(self::DEFAULT);
        if($val === null) $val = $this->directiveOrNull(self::DEFAULT);
        if($this->hasDirective(DIRECTIVE_KEY_GET)) return $this->getDirective(self::GET, $val);
        return $val;
    }

    public function setValue(mixed $value):void {
        if($this->isSet && $this->directiveOrNull(self::IMMUTABLE)) throw new ImmutableTypeError("This value is considered immutable and must not be changed.");
        $this->value = $value;
        $this->isSet = true;
    }

    public function setName(string $name):void {
        $this->{MODEL_RESERVERED_FIELD__FIELDNAME} = $name;
    }

    public function getName():string {
        return $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
    }

    public function setModel(GenericModel|NewGenericModel $model):void {
        $this->model = $model;
    }

    public function finalInitialization():void {

    }

    protected function getAttributes():array {
        return [
            'minlength' => $this->directiveOrNull('min'),
            'maxlength' => $this->directiveOrNull('max'),
        ];
    }

    /**
     * Each child of SchemaResult should return an appropriately typecast
     * version of the $value parameter
     * @param mixed $value 
     * @return mixed 
     */
    public function typecast($value, $type = QUERY_TYPE_CAST_LOOKUP) {
        if($this->type === "mixed") return $value;
        return compare_and_juggle($this->type, $value);
    }

    public function pre_filter($value):mixed {
        if($this->directiveOrNull(self::SKIP_VALIDATION)) {
            throw new ValidationSkip("Validation was skipped. Accepting raw inputs.");
        }
        if($this->hasDirective(self::TYPECAST)) {
            return compare_and_juggle($this->getDirective(self::TYPECAST),$value);
        }
        $value = $this->typecast($value);
        if($this->hasDirective(DIRECTIVE_KEY_FILTER)) {
            $value = $this->getDirective(DIRECTIVE_KEY_FILTER, $value);
        }
        return $value;
    }

    /**
     * Filters input from the client before the input is stored in the database
     * @param mixed $value the user input
     * @return mixed Returns the value to the be stored, may be transformed 
     */
    public function filter($value) {
        if($this->isSet && $this->directiveOrNull(self::IMMUTABLE)) throw new ImmutableTypeError("Cannot modify immutable field '".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."'");
        if($this->hasDirective(self::VALID)) {
            $this->getDirective(self::VALID);
        }
        // if($this->hasDirective(DIRECTIVE_KEY_FILTER)) $value = $this->getDirective(DIRECTIVE_KEY_FILTER, $value);
        return $value;
    }

    /*************** OVERLOADING  ***************/
    public function __get($property) {
        switch($property) {
            case "value":
                return $this->getValue();
            case 'isSet':
                return $this->isSet;
            case "raw":
            case "original":
                return $this->value ?? $this->originalValue;
            case "model":
                return $this->model;
            case "type":
                return gettype($this->value);
            case "name":
            case MODEL_RESERVERED_FIELD__FIELDNAME:
                return $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
            default:
                return null;
        }
    }

    public function __isset($property) {
        switch($property) {
            case "value":
                return $this->hasDirective('default') || $this->isSet;
            case "raw":
            case "original":
                return $this->isSet;
            case "name":
                return isset($this->{MODEL_RESERVERED_FIELD__FIELDNAME});
            case "model":
                return $this->hasModel;
            default:
                return false;
        }
    }

    public function __set($property, $value) {
        switch($property) {
            case "value":
                $this->__filter($value);
                break;
            // case "raw":
            // case "original":
            //     return $this->isSet;
            // case "name":
            //     return isset($this->{MODEL_RESERVERED_FIELD__FIELDNAME});
            // case "model":
            //     return $this->hasModel;
            default:
                // return false;
                throw new Undefined($property, "Cannot set $property.");
        }
    }

    public function __unset($property) {
        switch($property) {
            case "value":
                unset($this->value);
                break;
            default:
                throw new Undefined($property, "Property `$property` does not exist");
        }
    }

    public function __toString(): string {
        return (string)$this->getValue();
    }

    public function onUpdateConfirmed($value):void {
        update("[name='".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."']", ['value' => $this->value]);
        if($this->hasDirective(DIRECTIVE_KEY_ON_UPDATE)) {
            $this->getDirective(DIRECTIVE_KEY_ON_UPDATE, $value);
        }
    }

    /**
     * Returns a storable value in a string, number, or an array.
     * @return mixed 
     */
    public function serialize() {
        return $this->value;
    }

    public function offsetExists(mixed $offset): bool {
        return $this->__isset($offset);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        $this->__unset($offset);
    }

    static function typeFromValue(mixed $value, string $name):MixedType {
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
                    $instance->allow_undefined_fields(true);
                } else $instance = new ArrayType();
                break;
            case "object":
                $instance = static::normalizeMongoDocuments($value);
                break;
            default:
                $instance = new MixedType();
        }
        $instance->setName($name);
        $instance->setValue($value);

        return $instance;
    }

    static function normalizeMongoDocuments(&$value, $instance = null) {
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

    function getIndexAlignment() {
        return "left";
    }
}