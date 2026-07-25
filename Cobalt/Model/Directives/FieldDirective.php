<?php

namespace Cobalt\Model\Directives;

use Closure;
use Cobalt\Model\Directives\Abstracts\AbstractClosureDirective;
use Error;
use ReflectionFunction;

class FieldDirective extends AbstractClosureDirective {
    protected string $returnType = "string";
    protected int $passedByReference = -1;
    public function getValue(&...$args): mixed {
        return call_user_func($this->funct, $args);
    }
}