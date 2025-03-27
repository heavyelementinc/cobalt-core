<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class BinaryType extends MixedType {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-date";
        if($tag === null) $tag = "input-date";
        return $this->inputBinary($class, $misc, $tag);
    }
}