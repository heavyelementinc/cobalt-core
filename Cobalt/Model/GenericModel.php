<?php

namespace Cobalt\Model;

use ArrayAccess;
use Cobalt\Controllers\Traits\IndexableModel;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Exceptions\ReservedFieldName;
use Cobalt\Model\Exceptions\Undefined;
use Cobalt\Model\Traits\Filterable;
use Cobalt\Model\Traits\Hydrateable;
use Cobalt\Model\Traits\Schemable;
use Cobalt\Model\Traits\Viewable;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\Traits\Prototypable;
use DateTime;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Stringable;
use Traversable;
use TypeError;

use const Dom\NOT_FOUND_ERR;

/**
 * GenericModels may be accessed using the -> syntax *or* accessed as an array.
 *  * Accessing using the $model->key syntax will return an instance of the MixedType with the value, originalValue, its prototype methods, etc.
 *  * Accessing using the $model->['key'] syntax will return the literal value of the MixedType as if you accessed $model->key->value
 * 
 *  * `set()` - [&$validated, [$value]]
 * @package Cobalt\Model
 */
class GenericModel implements ArrayAccess, Iterator, Traversable, JsonSerializable, Stringable {
    use Schemable, Viewable, Hydrateable, Prototypable, Filterable;
    public ?string $name_prefix = null;
    protected bool $__schema_allow_undefined_fields = false;
    protected array $__reservedFields = [];
    protected ?string $name = "";
    protected ?string $fieldName = "";
    
    const DIRECTIVE_MAP = [
        'set' => 'Cobalt\Model\Directives\SetDirective',
    ];

    /*************** INITIALIZATION ***************/
    function __construct(?array $schema = [], null|array|BSONDocument|BSONArray $dataset = null, ?string $name_prefix = null, bool $allow_undefined_fields = false) {
        $this->name_prefix = $name_prefix;
        $this->set_allow_undefined_fields($allow_undefined_fields);
        $this->__defineSchema($schema);
        if(!$dataset || !empty($dataset)) $this->setData($dataset ?? []);
    }


    /*************** OVERLOADING ***************/
    public function __get($property) {
        // We store the _id separately, so we'll fetch that as a special case.
        if($property === "_id") return $this->_id; 
        if(in_array($property,$this->__systemFieldNames())) {
            return $this->__reservedFields[$property];
        }
        // Let's check to ensure that the property exists.
        if(key_exists($property, $this->__dataset)) return $this->__dataset[$property];
        throw new Undefined($property, "The property `$property` does not exist on `$property"."->".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."!");
    }

    public function __set($property, $value) {
        if(in_array($property, $this->__systemFieldNames())) {
            $this->__reservedFields[$property] = $value;
            return;
        }
        $reserved = $this->__reservedFieldNames();
        if(in_array($property, $reserved)) throw new ReservedFieldName("Cannot set $property as the name is reserved!");
        $ignored = ['__pclass'];
        if(in_array($property, $ignored)) return;
        
        // If we've already hydrated our property, we set the value and we're done
        if(key_exists($property, $this->__dataset)) {
            $this->__dataset[$property]->setValue($value);
            return;
        }
        
        // If we don't allow undefined schema fields
        if(!key_exists($property, $this->__schema) && !$this->__schema_allow_undefined_fields) {
            throw new TypeError("ERROR: `$property` is not a defined field. Type: " . gettype($value));
        }

        $this->hydrate(
            target: $this->__dataset,
            field_name: $property,
            value: $value,
            model: $this,
            name: (($this->name_prefix) ? "$this->name_prefix"."$property" : $property),
            directives: $this->__schema[$property] ?? []
        );
    }

    public function __isset($name) {
        if($name === "_id") return isset($this->_id);
        return key_exists($name, $this->__dataset) && $this->__dataset[$name]->isSet;
    }

    public function __unset($name) {
        unset($this->__dataset[$name]);
    }

    
    public function __toString(): string {
        return "[object GenericModel]";
    }
    
    /*************** ARRAY ACCESS ***************/
    public function offsetExists(mixed $offset): bool {
        if(!$this->__has_been_unserialized) return false;
        if(key_exists($offset, $this->__dataset)) return true;
        return false;
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->__dataset[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void { }

    /*************** ITERATOR ACCESS ***************/
    private int $index = 0;
    public function current(): mixed {
        return $this->__dataset[$this->key()];
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        return array_keys($this->__dataset)[$this->index];
    }

    public function valid(): bool {
        if($this->index < 0) return false;
        if(count($this->__dataset) < $this->index) return true;
        return false;
    }

    public function rewind(): void {
        $this->index = 0;
    }

    /*************** JSON SERIALIZATION ***************/
    public function jsonSerialize(): mixed {
        // $data = [];
        // foreach($this->__dataset as $field => $prop) {
        //     $data[$field] = $prop->value;
        // }
        // return $data;
        return $this->serialize();
    }

    public function serialize():mixed {
        $data = [];
        /** 
         * @var string $field
         * @var MixedType $value
         */
        foreach($this->__dataset as $field => $value) {
            // if(!isset($value)) continue;
            $data[$field] = $value->serialize();
        }
        return $data;
    }

    #[Prototype]
    protected function getName() {
        return substr($this->name_prefix,0,-1);
    }

    /************** ARCHIVAL STUFF **************/
    public function __isArchived(UTCDateTime|null $date = null):bool {
        $archived_time = $this->__archived;
        if(!$archived_time) return false;
        $archived_time = $archived_time->getValue();
        if($archived_time instanceof DateType || $archived_time instanceof UTCDateTime) {
            $archived_time = $archived_time->toDateTime()->format("u");
        } else {
            return false;
        }
        if($date === null) $date = (new DateTime())->format("u");
        else if($date instanceof UTCDateTime) $date = $date->toDateTime()->format("u");

        return $archived_time < $date;
    }

    public function get_allow_undefined_fields() {
        return $this->__schema_allow_undefined_fields;
    }

    public function set_allow_undefined_fields(bool $value) {
        $this->__schema_allow_undefined_fields = $value;
    }

}