<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

class BooleanResult extends SchemaResult {
    use Fieldable;
    protected $type = "boolean";
    
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    
    #[Prototype] protected function field($class = "", $misc = []) {
        return $this->inputSwitch($class, $misc);
    }

    #[Prototype]
    protected function equals($value) {
        $check = ($this->getValue() == $value);
        return ($this->asHTML) ? json_encode($check) : $check;
    }

    #[Prototype]
    protected function strictEquals($value) {
        $check = ($this->getValue() === $value);
        return ($this->asHTML) ? json_encode($check) : $check;
    }

    #[Prototype]
    protected function notEquals($value) {
        $check = ($this->getValue() != $value);
        return ($this->asHTML) ? json_encode($check) : $check;
    }

    #[Prototype]
    protected function display():string {
        $valid = $this->getValid();
        $val = $this->getValue();
        $str = ($val) ? "true" : "false";
        if(!empty($valid)) {
            if(key_exists($str, $valid)) return $valid[$str];
            if(key_exists($val, $valid)) return $valid[$val];
        }
        return $str;
    }

    public function __toString(): string {
        return ($this->getValue()) ? "true" : "false";
    }

    function filter($value) {
        if(is_bool($value)) return $value;
        if(\filter_var($value, FILTER_VALIDATE_BOOL)) {
            return (in_array($value, [1, '1', 'true', 'on', 'yes'])) ? true : false;
        }
        throw new ValidationIssue("This value is not a boolean value, nor can it be unambiguously converted to a boolean value.");
    }
}