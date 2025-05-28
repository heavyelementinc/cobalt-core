<?php

namespace Cobalt\Model\Directives;

use Closure;
use Error;
use ReflectionFunction;

class GetDirective extends SetDirective {
    /**
     * 
     * @param Closure $value 
     * @return void
     * @throws TypeError
     * @throws Error 
     */
    public function setValue(Closure $value): void {
        // $funcReflection = new ReflectionFunction($value);
        // $argsReflection = $funcReflection->getParameters();
        // if(!$argsReflection[0]->isPassedByReference()) {
        //     throw new Error("The first argument of set() must be accepted by reference.");
        // }
        // $returnType = $funcReflection->getReturnType();
        // if((string)$returnType !== "void") {
        //     throw new Error("The return type of set() must be explicitly void.");
        // }
        $this->value = $value;
    }
}