<?php

namespace Cobalt\Model\Directives\Abstracts;

use Closure;
use Error;
use ReflectionFunction;

abstract class AbstractClosureDirective {
    protected Closure $funct;
    protected string $returnType = "void";
    protected int $passedByReference = 0;

    function __construct(Closure $funct) {
        $funcReflection = new ReflectionFunction($funct);

        $returnType = $funcReflection->getReturnType();
        if((string)$returnType !== $this->returnType) {
            throw new Error("The filter closure must explicitly define a return type of `void`!");
        }

        $argsReflection = $funcReflection->getParameters();
        if($this->passedByReference >= 0) {
            if(!$argsReflection[$this->passedByReference]->isPassedByReference()) {
                throw new Error("The first argument must be passed by reference!");
            }
        }

        $this->funct = $funct;
    }
}