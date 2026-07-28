<?php

namespace Cobalt\Model\Directives;

use Closure;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;

class ValidDirective extends AbstractDirective {
    private array|Closure $value;
    function __construct(array|Closure $value)
    {
        $this->value = $value;
    }
    public function getValue(): mixed {
        // if($this->value instanceof Closure) return $this->{'value'}();
        return $this->value;
    }
}