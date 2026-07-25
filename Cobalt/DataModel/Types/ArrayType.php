<?php

namespace Cobalt\DataModel\Types;

use ArrayAccess;
use Cobalt\DataModel\Directives\Filters\Arrays\Each;
use Cobalt\DataModel\Filters\FilterFailed;
use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Traits\Overloading;
use Countable;
use Iterator;
use JsonSerializable;
use Override;
use TypeError;

/** 
 * @package Cobalt\DataModel\Types
 * */
class ArrayType extends Generic implements Iterator, ArrayAccess, Countable {
    use Overloading;
    protected array $keys = [];
    protected int $index = 0;

    #[Override]
    public function count(): int {
        return count($this->getValue());
    }

    #[Override]
    protected function composeFieldname(string|int $name): string {
        return "$this->fieldname.$name";
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        if(!is_array($toValidate)) {
            return $this->filterResult->addIssue($this, "Must be an array");
        }
        if(is_associative_array($toValidate)) {
            return $this->filterResult->addIssue($this, "Must not be an associative array");
        }
        // Implement min/max
        $count = count($toValidate);
        if($this->directives->min?->value && $count < $this->directives->min->value) { 
            $this->filterResult->addIssue(
                $this, 
                sprintf("This field needs at least %d values", $this->directives->min->value)
            );
        }
        if($this->directives->max?->value && $count > $this->directives->max->value) {
            $this->filterResult->addIssue(
                $this, 
                sprintf("This field must be no longer than %d values", $this->directives->max->value)
            );
        }



        // We only care if it throws a FilterIssue!
        try{ 
            $this->each($toValidate);
        } catch (TypeError $e) {
            $this->filterResult->addIssue($this, $e->getMessage(), $e->privateMessage ?? null);
            // throw new FilterFailed($this, $e->getMessage());
        }
        // Implement valid
        return $toValidate;
    }

    #[Override]
    public function setValue($mixed):void {
        // Make sure we return an array
        if(is_null($mixed) && $this->directives->nullable?->value == true) {
            $this->value = [];
            return;
        }
        // Keep things sane
        if(!is_array($mixed)) throw new TypeError("Must be an array");
        if(is_associative_array($mixed)) throw new TypeError("Must not be an associative array");

        $this->value = $mixed;
        $this->keys = array_keys($mixed);
        $this->index = 0;
    }

    
    #[Override]
    public function getValue(): mixed {
        return $this->each($this->value ?? []);
    }

    public function each(array $element, bool $filter = false):array {
        /** @var ?Generic $each */
        $each = $this->directives->each?->value;

        $elements = [];
        foreach($element as $key => $item) {
            $elements[$key] = $this->__hydrate($key, $item, $each);
 
            if($each && $elements[$key] instanceof $each === false) {
                throw new TypeError("Element `$key` must be of instance of ".$each::class);
            }
        }

        return $elements;
    }

    function __get($name) {
        switch($name) {
            case "value":
                return $this->getValue();
        }
        if(isset($this->value) && key_exists($name, $this->value)) {
            return $this->get($name);
        }
        return parent::__get($name);
    }

    function get($index) {
        return $this->__hydrate($index, $this->value[$index], $this->directives->each?->value);
    }

    public function includes(mixed $value):bool {
        return in_array($value, $this->value);
    }

    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value ?? [];
    }

    #[Override]
    function getValidComparisonValues(): ?array {
        return array_keys($this->value);
    }
    
    #[Override]
    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->value);
    }

    /**
     * Debate rages on as to whether we should allow access to this
     * @param mixed $offset 
     * @return mixed 
     */
    #[Override]
    public function offsetGet(mixed $offset): mixed {
        return $this->value[$offset] ?? null;
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->value[$offset] = $value;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void {
        unset($this->value[$offset]);
    }

    
    #[Override]
    public function current(): mixed {
        return $this->__hydrate($this->index, $this->value[$this->index], $this->directives->each?->value);
    }

    #[Override]
    public function next(): void {
        $this->index += 1;
    }

    #[Override]
    public function key(): mixed {
        return $this->index;
    }

    #[Override]
    public function valid(): bool {
        return key_exists($this->key(), $this->value ?? []);
    }

    #[Override]
    public function rewind(): void {
        $this->index = 0;
    }
    
}

    // /**
    //  * Push elements onto the end of array
    //  * @param mixed ...$values The pushed variables.
    //  * @return int the number of elements in the array.
    //  */

    // public function push():int{
    //     $int = array_push($this->value, ...func_get_args());
    //     $this->keys = array_keys($this->value);
    //     return $int;
    // }

    // public function pop():mixed {
    //     $popped = array_pop($this->value);
    //     $this->keys = array_keys($this->value);
    //     return $popped;
    // }

    // public function shift():mixed {
    //     $shifted = array_shift($this->value);
    //     $this->keys = array_keys($this->value);
    //     return $shifted;
    // }

    // /**
    //  * Prepend elements to the front of the array
    //  * @param mixed ...$values The pushed variables.
    //  * @return int the number of elements in the array.
    //  */

    // public function unshift():int{
    //     $int = array_unshift($this->value, ...func_get_args());
    //     $this->keys = array_keys($this->value);
    //     return $int;
    // }