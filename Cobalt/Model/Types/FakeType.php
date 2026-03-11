<?php

namespace Cobalt\Model\Types;
use Cobalt\Model\Attributes\Prototype;

class FakeType extends MixedType {
    function getValue(): mixed {
        return $this->directiveOrNull("value") ?? $this->directiveOrNull("get");
    }

    function initDirectives(): array {
        /** FakeTypes should always null out their value! */
        return [
            'set' => function (&$value) {$value = null;}
        ];
        
    }
}