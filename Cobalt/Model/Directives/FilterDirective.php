<?php

namespace Cobalt\Model\Directives;

use Closure;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Error;
use ReflectionFunction;

/**
 * The FilterDirective is called during the process of validating user input.
 * 
 * The filter directive is called after the isRequired and pattern directives
 * and before the class-based checks.
 * 
 * If you want to mutate your value BEFORE any other checks, use the MutateDirective
 * If you want to mutate your value AFTER all other checks, use the SetDirective
 * 
 * The supplied Closure must conform to the following parameters:
 *  * The first and only argument must be passed by reference (&$value)
 *  * The return value must be void
 * @package Cobalt\Model\Directives
 * @param Closure $funct [&$value]:void
 */
class FilterDirective extends AbstractDirective {
    private Closure $filter;
    /**
     * 
     * @param Closure $filter 
     * @return void 
     * @throws mixed 
     */
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