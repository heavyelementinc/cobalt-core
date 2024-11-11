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
use Countable;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONDocument;
use TypeError;

/**
 *  * hydrate - <bool> if false, arrays won't be hydrated
 * @package Cobalt\SchemaPrototypes\Basic
 */
class ArrayResult extends SchemaResult implements ArrayAccess, Iterator, Traversable, Countable{
    use ResultTranslator, Fieldable;
    protected $type = "array";
    protected $__index = 0;

    public function count(): int {
        return count($this->value);
    }

    function setValue($value):void {
        $this->originalValue = $value;
        $array = $value;
        if($value instanceof BSONArray) $array = $value->getArrayCopy();
        if(empty($value)) $array = $this->schema['default'];
        $array = $this->__each($array, $this->schema['each'] ?? []);
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
        $storable = [];
        foreach($this->value as $index => $value) {
            $storable[$index] = $value->getRaw();
        }
        return $storable;
    }
    
    function filter($value) {
        if(!is_array($value)) {
            throw new ValidationIssue("Value must be an array");
        }

        $strict = $this->isStrict();
        if($strict) {
            // $valid = $this->getDirective('valid');
            // if($valid) {
            //     $intersection = array_intersect($value, $valid);
            // }
        }
        return $value;
    }

    function defaultSchemaValues(array $data = []): array {
        return [
            'strict' => false,
            'allow_custom' => false
        ];
    }

    #[Prototype]
    protected function options($selected = null): string {
        $valid = $this->getValid();
        if($selected) {
            if($this->getDirective("allow_custom")) $val = $selected;
            else if (in_array($selected, $valid)) $val = $selected;
            else $val = $this->getValue() ?? $this->value;
        } else $val = $this->getValue() ?? $this->value;
        // $val = $this->getRaw();// ?? $this->value;
        if(!is_string($val) && is_numeric($val)) $val = (string)$val;
        // if($val instanceof \MongoDB\Model\BSONArray) $gotten_value = $val->getArrayCopy();
        
        // If custom is allowed
        $allow_custom = $this->getDirective("strict") === false;
        if(!$allow_custom) $allow_custom = $this->getDirective("allow_custom");

        // If the current value is not a key in the current valid options AND
        // we're allowed to have custom options, add the current val to the options
        if($allow_custom && $val) $valid += array_diff($valid, $val);

        $type = gettype($val);

        switch ($type) {
                // case $val instanceof \MongoDB\Model\BSONArray:
                //     $val = $val->getArrayCopy();
            case "array":
                $validValue = [];
                foreach ($val as $o) {
                    $validValue[(string)$o] = $o;
                }
                $valid = array_merge($validValue ?? [], $valid ?? []);
                $type = gettype($val);
        }

        $options = "";
        foreach ($valid as $validKey => $validValue) {
            $value = $validValue;
            $data = "";
            if (gettype($validValue) === "array") {
                $validValue = $validValue['value'];
                unset($value['value']);
                foreach ($value as $attr => $val) {
                    $data .= " data-$attr=\"$val\"";
                }
            }
            $selected = "";
            switch ($type) {
                case "string":
                case "integer":
                case "double":
                    $selected = ($val == $validKey) ? "selected='selected'" : "";
                    break;
                case "object":
                    if ($val instanceof \MongoDB\BSON\ObjectId && (string)$val === $validKey) {
                        $selected = "selected='selected'";
                    }
                    break;
                case "array":
                    $selected = (in_array($validKey, $val)) ? "selected='selected'" : "";
                    break;
            }
            $options .= "<option value='$validKey'$data $selected>$validValue</option>";
        }
        return $options;
    }

    function getRaw(): mixed {
        if($this->originalValue instanceof BSONArray) return $this->originalValue->getArrayCopy();
        return $this->originalValue;
    }
    
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/

    #[Prototype]
    protected function field($classes = "", $misc = [], $tag = "") {
        if($this->getDirective("view") || $this->getDirective("template")) return $this->inputObjectArray($classes, $misc);
        if($this->getDirective("allow_custom")) $misc['allow-custom'] = "true";
        return $this->inputArray($classes, $misc);
    }

    #[Prototype]
    protected function display():string {
        $value = $this->getValue();
        $valid = $this->getValid();
        $result = [];
        foreach($value as $key) {
            if($key instanceof SchemaResult) $key = (string)$key;
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
        $array = $this->getValue();
        if($array instanceof BSONDocument) $array = $array->getArrayCopy();
        $val = implode($delimiter, $array ?? []);
        return $val;
    }

    #[Prototype]
    protected function last() {
        $val = $this->getValue() ?? [];
        $v = count($val);
        return $val[$v - 1];
    }

    #[Prototype]
    protected function intersect($arraylike) {
        $arr = $this->arraylike_to_array($arraylike);
        return array_intersect($this->getRaw() ?? [], $arr);
    }

    #[Prototype]
    protected function includes(string $needle) {
        $value = $this->getValue();
        if($value instanceof BSONDocument) {
            $value = $value->getArrayCopy();
        }
        return in_array($needle, $value);
    }

    #[Prototype]
    protected function key_exists($needle) {
        $value = (array)$this->getValue();
        // $value = $value->getArrayCopy();
        return in_array($needle, $value);
    }

    #[Prototype]
    protected function getArrayCopy():array {
        return $this->arraylike_to_array($this);
    }

    private function arraylike_to_array($arraylike):array {
        if(gettype($arraylike) !== "array") {
            if($arraylike instanceof ArrayResult) {
                $arraylike = $arraylike->getRaw();
            }
            if($arraylike instanceof BSONArray) {
                $arraylike = $arraylike->getArrayCopy();
            }
            if(gettype($arraylike) !== "array") {
                throw new TypeError("arraylike must be an array");
            }
        }
        return $arraylike;
    }

    function valid():bool {
        $val = $this->getValue();
        if(is_null($val)) return false;
        if($val instanceof Iterator) $val = iterator_to_array($val);
        if($val instanceof BSONDocument) $val = iterator_to_array($val);
        $key = array_keys($val ?? [])[$this->__index];
        if(is_null($key)) return false;
        if(key_exists($key, $val)) return true; 
        return false;
    }

    public function __toString():string {
        if($this->__isPrivate()) return "";
        $string = $this->join(", ");
        if(gettype($string) !== "string") return json_encode($string);
        return $string;
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, (array)$this->getValue());
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
        if($val instanceof BSONDocument) {
            $val = iterator_to_array($val);
        }
        $keys = array_keys($val);
        return $keys[$this->__index];
    }

    public function rewind(): void {
        $this->__index = 0;
    }

}