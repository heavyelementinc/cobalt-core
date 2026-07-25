<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class MarkdownType extends StringType {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-markdown";
        if($tag === null) $tag = "input-markdown";
        return $this->markdownarea($class, $misc, $tag);
    }
}