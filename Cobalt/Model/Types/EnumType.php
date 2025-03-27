<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Types\Traits\SharedFilterEnums;
use Cobalt\Model\Attributes\Prototype;

use Stringable;

class EnumType extends MixedType implements Stringable {
    use SharedFilterEnums;
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "select";
        if($tag === null) $tag = "select";
        return $this->select($class, $misc, $tag);
    }
}