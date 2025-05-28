<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class NumberType extends MixedType {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null) $tag = $this->directiveOrNull("input_tag") ?? "input";
        return $this->input($class, $misc, $tag);
    }
}