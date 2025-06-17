<?php

namespace Cobalt\Model\Directives;

use Closure;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Cobalt\Model\Types\MixedType;

class GenericDirective extends AbstractDirective {
    protected mixed $value;
    function __construct(mixed $value) {
        $this->setValue($value);
    }

    public function getValue(): mixed {
        if(is_callable($this->value)) {
            $func = $this->value;
            return $func(...func_get_args());
        }
        return $this->value;
    }

    public function setValue(mixed $value): void {
        $this->value = $value;
    }

}