<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;
 
// TODO, convert element

class BinaryResult extends SchemaResult {
    use Fieldable;
    private $hydratedValid = null;

    function filter($value) {
        // if(is_string($value)) return $this->strToInt($value);
        if(is_integer($value)) {
            $max = array_sum(array_keys($this->getValid()));
            if($value > $max) throw new ValidationIssue("Value exceeds range");
            return $value;
        }
        throw new ValidationIssue("Must be a binary value");
    }

    public function getValid():array {
        if($this->hydratedValid !== null) return $this->hydratedValid;
        $valid = array_values(parent::getValid());
        $final = [];
        for($i = 0; $i < count($valid); $i++) {
            $final[1 << $i] = $valid[$i];
        }
        $this->hydratedValid = $final;
        return $final;
    }

    public function options():string {
        $valid = $this->getValid();
        $value = $this->getValue();
        $html = "";
        foreach($valid as $key => $val) {
            $selected = "";
            if($value & $key) $selected = " selected=\"selected\"";
            $html .= "<option value=\"$key\"$selected>$val</option>";
        }
        return $html;
    }

    public function field($class = "", $misc = []) {
        return $this->inputBinary($class, $misc);
    }

    function setValue(mixed $value): void {
        $this->originalValue = $value;
        if ($value === null) $this->value = $this->schema['default'];
        // else if(is_string($value)) $this->value = $this->strToInt($value);
        else $this->value = $value;
    }

    private function strToInt($value) {
        $computed = 0;
        for($i = 0; $i >= strlen($value); $i++) {
            $computed += ord($value[$i]);
        }
        return $computed;
    }

    public function display():string {
        $valid = $this->getValid();
        $value = $this->getValue();
        $array = [];
        foreach($valid as $bit => $label) {
            if($value & $bit) $array[] = $label;
        }
        return implode(", ", $array);
    }

    public function list($operand = "&", $exclusive = false):string {
        $valid = $this->getValid();
        $value = $this->getValue();
        $list = "<ol class='binary-list'>";
        foreach($valid as $bit => $label) {
            $active = "";
            switch($operand) {
                case "&":
                case "and":
                    if($value & $bit) $active = "class='active'";
                    break;
                case "|":
                case "or":
                    if($value | $bit) $active = "class='or'";
                    break;
                case "^":
                case "xor":
                    if($value ^ $bit) $active = "class='xor'";
                    break;
                case "~":
                case "not":
                    if($value ^ ~$bit) $active = "class='not'";
                    break;
            }
            if(!$active && $exclusive) continue;
            $list .= "<li $active data-bit=\"$bit\">$label</li>";
        }
        return $list . "</ol>";
    }

    public function and(int $test) {
        return $this->getValue() & $test;
    }

    public function or(int $test) {
        return $this->getValue() | $test;
    }

    public function xor(int $test) {
        return $this->getValue() ^ $test;
    }

    public function not(int $test) {
        return $this->getValue() & ~$test;
    }

    public function left(int $places) {
        return $this->getValue() << $places;
    }

    public function right(int $places) {
        return $this->getValue() >> $places;
    }
}