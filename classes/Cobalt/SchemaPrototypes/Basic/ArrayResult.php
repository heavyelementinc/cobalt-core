<?php
/**
 * The ArrayResult is a basic prototype that wraps an Array.
 *  
 * @package Cobalt\SchemaPrototypes
 * @author Gardiner Bryant, Heavy Element
 * @copyright 2023 Heavy Element
 */

namespace Cobalt\SchemaPrototypes\Basic;

use ArrayAccess;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Iterator;
use MongoDB\Model\BSONArray;
use Traversable;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use MongoDB\BSON\Persistable;

class ArrayResult extends SchemaResult implements ArrayAccess, Iterator, Traversable{
    use ResultTranslator, Fieldable;
    protected $type = "array";
    protected $__index = 0;

    function setValue($value):void {
        $this->originalValue = $value;
        $array = $value;
        if($value instanceof BSONArray) $array = $value->getArrayCopy();
        if(empty($value)) $array = $this->schema['default'];
        $array = $this->__each($array, $this->__reference);
        $this->value = $array;
    }

    function eachToView(string $view, array $vars = []):string {
        $html = "";
        $fn = "view_from_string";
        if(template_exists($view)) $fn = "view";
        foreach($this->value as $val) {
            $html .= $fn($view, array_merge($vars,['doc' => $val]));
        }
        return $html;
    }

    public function __getStorable(): mixed {
        if($this instanceof Persistable) return $this;
        return $this->value;
    }
    
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/

    #[Prototype]
    protected function field($classes = "", $misc = [], $tag = "") {
        return $this->inputArray($classes, $misc);
    }

    #[Prototype]
    protected function display():string {
        $value = $this->getValue();
        $valid = $this->getValid();
        $result = [];
        foreach($value as $key) {
            switch(gettype($valid)) {
                case "array":
                    if(key_exists($key, $valid)) $result[] = "<li>$valid[$key]</li>";
                    break;
                case "object":
                    if(is_a($valid, "ArrayAccess")) $result[] = "<li>$valid[$key]</li>";
                    break;
                default:
                    $result[] = "<li>$key</li>";
                    break;
            }
        }

        return "<ul>" . implode("",$result) . "</ul>";
    }

    #[Prototype]
    protected function push() {
        $each = null;
        if(isset($this->schema['each'])) $each = $this->schema['each'];

        $args = func_get_args();

        if($each && $each instanceof SchemaResult) {
            array_push($this->value, ...$this->__each($args, $this->__reference, count($this->value)));
            return;
        }
        
        array_push($this->value, ...$args);
        return $this->getValue();
    }

    #[Prototype]
    protected function pop() {
        return array_pop($this->value);
    }

    #[Prototype]
    protected function shift() {
        return array_shift($this->value);
    }

    #[Prototype]
    protected function unshift() {
        $each = null;
        if(isset($this->schema['each'])) $each = $this->schema['each'];

        $args = func_get_args();

        if($each && $each instanceof SchemaResult) {
            array_unshift($this->value, ...$this->__each($args, $this->__reference, count($this->value)));
            return;
        }

        array_unshift($this->value, ...$args);
        return $this->getValue();
    }

    #[Prototype]
    protected function join($delimiter) {
        return implode($delimiter, $this->getValue());
    }

    #[Prototype]
    protected function last() {
        $val = $this->getValue();
        $v = count($val);
        return $val[$v - 1];
    }



    function valid():bool {
        $val = $this->getValue();
        if(key_exists(array_keys($val)[$this->__index], $val)) return true; 
        return false;
    }

    public function __toString():string {
        return $this->join(", ");
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->getValue());
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->getValue()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        // $this->value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        // unset($this->value[$offset]);
    }

    public function current(): mixed {
        $val = $this->getValue();
        return $val[$this->key()];
    }

    public function next(): void {
        $this->__index++;
    }

    public function key(): mixed {
        $val = $this->getValue();
        $keys = array_keys($val);
        return $keys[$this->__index];
    }

    public function rewind(): void {
        $this->__index = 0;
    }

    function filter($value) {
        if(!is_array($value)) {
            // if(is_a())
            throw new ValidationIssue("Value must be an array");
        }
        return $value;
    }
}