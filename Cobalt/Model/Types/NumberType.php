<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class NumberType extends MixedType {
    protected string $type = "number";
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null) $tag = $this->directiveOrNull("input_tag") ?? "input";
        return $this->input($class, $misc, $tag);
    }

    /**
     * Each child of MixedType should return an appropriately typecast
     * version of the $value parameter
     * @param mixed $value 
     * @return mixed 
     */
    public function typecast($value, $type = QUERY_TYPE_CAST_LOOKUP) {
        if($this->type === "mixed") return $value;
        return match(gettype($value)) {
            "integer" => $value,
            "double" => $value,
            "float" => $value,
            // Let's convert a string based on if it has a . in it or not
            "string" => (strpos($value,".") === false) ? intval($value) : floatval($value),
            "boolean" => intval($value),
            // If $value is empty, we want to return a 0
            "array" => empty($value) ? 0 : 1,
            "NULL" => 0,
            "object" => 1,
            "resource" => 1,
            default => intval($value),
        };
        return compare_and_juggle($this->type, $value);
    }
}