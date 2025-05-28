<?php

namespace Cobalt\Model\Directives;

use Closure;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Error;
use ReflectionFunction;

/**
 * The FilterDirective is called during the process of validating user input.
 * The supplied Closure must conform
 * @package Cobalt\Model\Directives
 */
class FilterDirective extends AbstractDirective {
    private Closure $filter;
    function __construct(Closure $filter) {
        $funcReflection = new ReflectionFunction($filter);
        $argsReflection = $funcReflection->getParameters();
        if(!$argsReflection[0]->isPassedByReference()) {
            throw new Error("The first argument must be assed by reference!");
        }
        $returnType = $funcReflection->getReturnType();
        if((string)$returnType !== "void") {
            throw new Error("The filter closure must explicitly define a return type of `void`!");
        }
        $this->filter = $filter;
    }
    public function getValue(&...$args): mixed {
        return call_user_func_array($this->filter, $args);
    }

}