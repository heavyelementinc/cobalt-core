<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class NumberType extends MixedType {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-number";
        if($tag === null) $tag = "input-number";
        return $this->input($class, $misc, $tag);
    }
}