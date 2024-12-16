<?php

namespace Cobalt\Model;

use ArrayAccess;
use Cobalt\Model\Exceptions\Undefined;
use Cobalt\Model\Traits\Hydrateable;
use Cobalt\Model\Traits\Schemable;
use Cobalt\Model\Traits\Viewable;
use Iterator;
use JsonSerializable;
use MongoDB\Model\BSONDocument;
use Stringable;
use Traversable;

/**
 * GenericModels may be accessed using the -> syntax *or* accessed as an array.
 *  * Accessing using the $model->key syntax will return an instance of the MixedType with the value, originalValue, its prototype methods, etc.
 *  * Accessing using the $model->['key'] syntax will return the literal value of the MixedType as if you accessed $model->key->value
 * @package Cobalt\Model
 */
class GenericModel implements ArrayAccess, Iterator, Traversable, JsonSerializable, Stringable {
    use Schemable, Viewable, Hydrateable;
    public ?string $name_prefix = null;

    /*************** INITIALIZATION ***************/
    function __construct(?array $schema = [], null|array|BSONDocument $dataset = null, ?string $name_prefix = null) {
        $this->name_prefix = $name_prefix;
        $this->__defineSchema($schema);
        if(!$dataset || !empty($dataset)) $this->setData($dataset ?? []);
    }


    /*************** OVERLOADING ***************/
    public function __get($property) {
        // We store the _id separately, so we'll fetch that as a special case.
        if($property === "_id") return $this->_id; 
        // Let's check to ensure that the property exists.
        if(key_exists($property, $this->__dataset)) return $this->__dataset[$property];
        throw new Undefined($property, "The property $property does not exist!");
    }

    public function __set($property, $value) {
        $reserved = [];
        if(in_array($property, $reserved)) throw new \TypeError("Cannot set $property as the name is reserved!");
        $this->hydrate($this->__dataset, $property, $value, null, $this, (($this->name_prefix) ? "$this->name_prefix"."$property" : null));
    }

    public function __isset($name) {
        if($name === "_id") return isset($this->_id);
        return isset($this->__dataset[$name]->value);
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
        return $this->__dataset[$offset]->value;
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
        $data = [];
        foreach($this->__dataset as $field => $prop) {
            $data[$field] = $prop->value;
        }
        return $data;
    }
}