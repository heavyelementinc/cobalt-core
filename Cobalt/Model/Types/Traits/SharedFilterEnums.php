<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Types\MixedType;
use Exception;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

trait SharedFilterEnums {

    /**
     *
     * @param array $value
     * @param string $name
     * @return Cobalt\Model\Traits\MixedType
     */
    #[Directive]
    public function define_valid(array $value, string $name):MixedType {
        $this->__defineDirective($name, $value);
        return $this;
    }

    #[Prototype]
    public function display():mixed {
        $valid = [];
        if($this->hasDirective("valid")) $valid = $this->getDirective("valid");
        $result = "";
        
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
        if (isset($this->schema['valid'])) {
            if (is_callable($this->schema['valid'])) {
                $val = $this->getDirective('valid');
                if (is_array($val)) return $val;
                if ($val instanceof BSONArray) return $val->getArrayCopy();
                if ($val instanceof BSONDocument) return (array)$val;
                if (is_iterable($val)) return iterator_to_array($val);
                throw new Exception("Return value for $this->name's `valid` directive is not an array or iterable!");
            }
            return $this->schema['valid'];
        }
        return [];
    }

    /**
     * The `options` method will return an string of <option> tags based on
     * the return value of the `getValid()` method. The current value of this
     * field will have the `selected="selected"` attribute set.
     * 
     * This is useful for the native <select> element, the <input-array> component,
     * and the <input-autocomplete> component.
     * @return string
     */
    #[Prototype]
    protected function options($selected = null): string {
        $valid = $this->getValid();
        
        if($selected) {
            if($this->getDirective("allow_custom")) $val = $selected;
            else if (key_exists($selected, $valid)) $val = $selected;
            else $val = $this->getValue() ?? $this->value;
        } else $val = $this->getValue() ?? $this->value;

        // if(!is_string($val) && is_numeric($val)) $val = "$val";
        // if($val instanceof \MongoDB\Model\BSONArray) $gotten_value = $val->getArrayCopy();
        
        // If custom is allowed
        $allow_custom = $this->getDirective("strict") === false;
        if(!$allow_custom) $allow_custom = $this->getDirective("allow_custom");

        // If the current value is not a key in the current valid options AND
        // we're allowed to have custom options, add the current val to the options
        if($allow_custom && $val && !key_exists($val, $valid)) $valid += [$val => $val];

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
}