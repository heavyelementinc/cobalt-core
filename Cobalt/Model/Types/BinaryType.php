<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class BinaryType extends MixedType {
    protected $type = "boolean";
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-date";
        if($tag === null) $tag = "input-date";
        return $this->inputBinary($class, $misc, $tag);
    }

    /**
     * Each child of MixedType should return an appropriately typecast
     * version of the $value parameter
     * @param mixed $value 
     * @return mixed 
     */
    public function typecast($value, $type = QUERY_TYPE_CAST_LOOKUP) {
        return filter_var($value, FILTER_VALIDATE_BOOL);
        
    }
}