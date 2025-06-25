<?php

namespace Cobalt\Models\Directives;

use Closure;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Error;
use ReflectionFunction;

/**
 * The MutateDirective is called during the process of validating user input.
 * 
 * The `mutate` directive is called before *any other checks* including the
 * default check.
 * 
 * If you want to apply custom filtering to your value before the class-level filter, use the FilterDirective
 * If you want to mutate your value AFTER all other checks, use the SetDirective
 * 
 * The supplied Closure must conform to the following parameters:
 *  * The first and only argument must be passed by reference (&$value)
 *  * The return value must be void
 * @package Cobalt\Model\Directives
 * @param Closure $funct [&$value]:void
 */
class MutateDirective extends AbstractDirective {
    private Closure $filter;
    function __construct(Closure $filter) {
        $funcReflection = new ReflectionFunction($filter);
        $argsReflection = $funcReflection->getParameters();
        if(!$argsReflection[0]->isPassedByReference()) {
            throw new Error("The first argument must be passed by reference!");
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