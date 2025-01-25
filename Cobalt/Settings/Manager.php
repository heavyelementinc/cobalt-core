<?php

namespace Cobalt\Options;

use ArrayAccess;
use Exception;
use Iterator;

class Manager implements ArrayAccess, Iterator {
    
    function __construct($values) {
        // $this->index = 0;
        if(gettype($values) !== "array") throw new Exception("Values are not an array");
        $this->__values = $values;
    }

    private $__values = [];
    private $index = 0;

    function __get($name) {
        if(!isset($this->__values[$name])) throw new Exception ("$name is not a recognized setting");

        // $instantiable = "\\Cobalt\\Settings\\Definitions\\$name";

        // return new $instantiable($this->__values[$name], $this->__values);
    }

    // function __set($name, $value) {
        
    // }


    /** ARRAY ACCESS */
    public function offsetExists(mixed $offset): bool {
        return in_array($offset,array_keys($this->__values));
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->__values[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        // return 
        $this->__values[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->__values[$offset]);
    }

    /** ITERATOR */
    public function current(): mixed {
        return $this->{$this->key};
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        $keys = array_keys($this->__values);
        return $keys[$this->index];
    }

    public function valid(): bool {
        if($this->index > count($this->__values)) return false;
        if(!isset($this->__values[$this->key()])) return false;
        return true;
    }

    public function rewind(): void {
        $this->index = 0;
    }

}