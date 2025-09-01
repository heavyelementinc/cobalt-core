<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BinaryType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\WeakEnumType;
use Error;
use Exception;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Stringable;

trait SharedFilterEnums {

    /**
     *
     * @param array $value
     * @param string $name
     * @return Cobalt\Model\Traits\MixedType
     */
    #[Directive]
    public function define_valid($value, string $name):MixedType {
        $this->__defineDirective($name, $value);
        return $this;
    }

    #[Prototype]
    public function display(): mixed {
        $valid = [];
        if($this->hasDirective("valid")) $valid = $this->getDirective("valid");
        
        if(empty($valid ?? [])) return $this->value;
        // if((is_string($this->value) || is_int($this->value)) && key_exists($this->value, $valid)) return $valid[$this->value];
        switch(gettype($this->value)) {
            case "string":
            case "integer":
                return $valid[$this->value];
            case "object":
                if($this->value instanceof Stringable) return $valid[(string)$this->value];
        }
        if($this instanceof WeakEnumType) return $this->value;
        return "";
    }

    /**
     * Get the list of valid values for this field. This is defined by the 
     * `valid` array or delta function directive.
     * 
     * `valid` delta directives MUST return an array or iterable.
     * 
     * @return array
     */
    #[Prototype]
    protected function getValid(): array {
        // if ($field === "pronoun_set") return $this->valid_pronouns();
        if ($this->hasDirective('valid')) {
            $val = $this->getDirective('valid');
            if (is_array($val)) return $val;
            if ($val instanceof BSONArray) return $val->getArrayCopy();
            if ($val instanceof BSONDocument) return (array)$val;
            if (is_iterable($val)) return iterator_to_array($val);
            throw new Exception("Return value for ".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."'s `valid` directive is not an array or iterable!");
        }
        return [];
    }

    /**
     * The `options` method will return a string of <option> tags based on
     * the return value of the `getValid()` method. The current value of this
     * field will have the `selected="selected"` attribute set.
     * 
     * This is useful for the native <select> element, the <input-array> component,
     * and the <input-autocomplete> component.
     * @return string
     */
    #[Prototype]
    public function options($selected = null): string {
        $valid = $this->getValid();
        
        if($selected) {
            // if($this->hasDirective('allow_custom') && $this->getDirective("allow_custom")) $val = $selected;
            if (key_exists($selected, $valid)) $val = $selected;
            else $val = $this->getValue() ?? $this->value;
        } else $val = $this->getValue() ?? $this->value;

        // if(!is_string($val) && is_numeric($val)) $val = "$val";
        // if($val instanceof \MongoDB\Model\BSONArray) $gotten_value = $val->getArrayCopy();

        $options = "";

        // Here we determine if we allow custom values and add to our list of options
        $allow_custom = false;
        if($this->hasDirective('strict')) {
            $allow_custom = $this->getDirective("strict") === false;
        }
        if($this->hasDirective('allow_custom')) {
            $allow_custom = $this->getDirective("allow_custom");
        }
        $type = gettype($val);
        if($allow_custom) $this->integrate_custom_values_to_valid_array($val, $valid, $type);

        $is_nullable = $this->directiveOrNull('nullable');
        if($is_nullable) {
            $options .= "<option value=\"\" is-null=\"true\">".($this->directiveOrNull('empty_label') ?? "-- Select --")."</option>";
        }

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
            if($this instanceof ArrayType) {
                $selected = ($this->includes($validKey)) ? "selected='selected'": "";
            } else if ($this instanceof BinaryType) {
                $selected = ($this->and($validKey)) ? "selected='selected'" : "";
            } else {
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
            }
            $options .= "<option value='$validKey'$data $selected>$validValue</option>";
        }
        return $options;
    }

    #[Prototype]
    public function datalist($selected = null, $name = null) {
        if(!$name) $name = $this->datalist_name();
        return "<datalist id=\"$name\">".$this->options($selected)."</datalist>";
    }

    public function datalist_name() {
        $name = str_replace(".","_",$this->name);
        return "datalist_$name";
    }

    private function integrate_custom_values_to_valid_array(mixed $val, array &$valid, ?string $type = null) {
        if(is_null($type)) $type = gettype($val);
    
        switch ($type) {
                // case $val instanceof \MongoDB\Model\BSONArray:
                //     $val = $val->getArrayCopy();
            case "string":
            case "int":
                // If the current value is not a key in the current valid options AND
                // we're allowed to have custom options, add the current val to the options
                if(!key_exists($val ?? "", $valid)) $valid += [$val => $val];
                break;
            case "array":
                $validValue = [];
                foreach ($val as $o) {
                    // If the current value is not a key in the current valid options AND
                    // we're allowed to have custom options, add the current val to the options
                    if($o instanceof MixedType) $o = $o->value;
                    if(!key_exists($o ?? "", $valid)) $valid[$o] = $o;
                    $validValue[(string)$o] = $o;
                }
                $valid = array_merge($validValue ?? [], $valid ?? []);
                $type = gettype($val);
        }
    }
}