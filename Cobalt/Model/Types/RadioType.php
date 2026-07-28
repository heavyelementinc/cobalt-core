<?php

namespace Cobalt\Model\Types;

class RadioType extends EnumType {
    function field(string $class = "", array $misc = [], ?string $tag = null): string {
        return $this->select("", [], "input-radio");
    }

    function initDirectives(): array
    {
        $result = parent::initDirectives();
        $result['nullable'] = false;
        return $result;
    }

    public function finalInitialization():void {
        $this->__defineDirective('nullable', false);
    }
}