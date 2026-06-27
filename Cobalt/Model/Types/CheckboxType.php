<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class CheckboxType extends BooleanType {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-switch";
        if($tag === null) $tag = "input";
        if($this->value === true) $misc['checked'] = "checked";
        return $this->input($class, $misc, $tag);
    }

    public function typecast($value, $type = QUERY_TYPE_CAST_LOOKUP) {
        if(is_string($value)) $value = strtolower($value);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}