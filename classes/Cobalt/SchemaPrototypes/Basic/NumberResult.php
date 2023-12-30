<?php

namespace Cobalt\SchemaPrototypes\Basic;

use ArrayAccess;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;

class NumberResult extends SchemaResult implements ArrayAccess{
    use Fieldable;
    protected $type = "number";

    public function defaultSchemaValues(array $data = []): array
    {
        return array_merge([
            'min' => null,
            'max' => null,
            'pattern' => null,
        ], $data);
    }

    public function add($operand) {
        return $this->getValue() + $operand;
    }

    public function subtract($operand, $largerFromSmaller = true) {
        $sub = $this->getValue() - $operand;
        return ($largerFromSmaller) ? abs($sub) : $sub;
    }

    public function multiply($operand) {
        return $this->getValue() * $operand;
    }

    public function divide($operand, $largerFromSmaller = true) {
        $val = $this->getValue();
        if($largerFromSmaller === true) [$val, $operand] = $this->sort($operand, SORT_DESC);
        return $val / $operand;
    }

    public function divideFrom($operand) {
        $val = $this->getValue();
        return $operand / $val;
    }

    public function percent($operand, $scale = 100, $round = false) {
        $larger = $this->getValue();
        $smaller = $operand;
        $result = ($larger / $smaller) * $scale;
        if($round) return round($result, $round);
        return $result;
    }

    public function modulo($operand) {
        return $this->getValue() % $operand;
    }

    public function sort($number, $order = SORT_ASC) {
        $sortable = [$this->getValue(), $number];
        sort($sortable, $order);
        return $sortable;
    }
    
    public function max() {
        return max($this->getValue(), func_get_args());
    }

    public function min() {
        return min($this->getValue(), func_get_args());
    }

    public function format($decimals = 0, $decimal_separator = ".", $thousands_separator = ",") {
        $fmt = number_format($this->getValue(), $decimals, $decimal_separator, $thousands_separator);
        return $fmt;
    }

    public function __toString(): string {
        return (string)$this->getValue();
    }

    public function offsetExists(mixed $offset): bool {
        $length = count($this->getValue());
        if($offset < 0) return false;
        if($length >= $offset) return false;
        return true;
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->getValue()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        return;
    }

    public function offsetUnset(mixed $offset): void {
        return;
    }

    public function filter($value) {
        $pass = false;
        switch(gettype($value)) {
            case "string":
                if(is_numeric($value)) $pass = true;
                break;
            case "integer":
            case "double":
            case "float":
                $pass = true;
                break;
        }

        if(!$pass) throw new ValidationIssue("The supplied value is not numeric");
        $min = $this->schema['min'];
        $max = $this->schema['max'];
        if($min !== null && $value < $min) throw new ValidationIssue("Supplied value is lower than minimum ($min)");
        if($max !== null && $value > $max) throw new ValidationIssue("Supplied value is grearer than maximum ($max)");
        return $value;
    }

    public function field($classes = "", $misc = []):string {
        $tag = $misc['tag'] ?? 'input';
        $type = $misc['type'] ?? $this->type;
        $min = ($this->schema['min']) ? " min=\"".$this->schema['min']."\"" : "";
        $max = ($this->schema['max']) ? " max=\"".$this->schema['max']."\"" : "";
        $pattern = ($this->schema['pattern']) ? " pattern=\"".$this->schema['pattern']."\"" : "";

        // return "<$tag " . $this->defaultFieldData($misc);
        return "<input-number name=\"$this->name\" value=\"".$this->getValue()."\"".$min.$max.$pattern."></input-number>";
    }

        /** Currently, these do not behave as expected because
     * each {{num.increment()}} fetches a new instance of
     * the NumberResult. Therefore later modification of the
     * $this->value only modifies this instance.
     */
    // public function increment($operand = 1) {
    //     $this->value = $this->getValue() + $operand;
    //     return $this->value;
    // }

    // public function decrement($operand = 1) {
    //     $this->value = $this->getValue() - $operand;
    //     return $this->value;
    // }

    // public function resetCounter() {
    //     $this->value = $this->originalValue;
    //     return $this->value;
    // }
}