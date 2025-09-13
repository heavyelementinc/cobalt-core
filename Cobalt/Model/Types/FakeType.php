<?php

namespace Cobalt\Model\Types;
use Cobalt\Model\Attributes\Prototype;

class FakeType extends MixedType {
    function getValue(): mixed {
        return $this->directiveOrNull("value") ?? $this->directiveOrNull("get");
    }
}