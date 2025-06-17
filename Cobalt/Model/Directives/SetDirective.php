<?php

namespace Cobalt\Model\Directives;

use Closure;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Error;
use Exception;
use ReflectionFunction;
use TypeError;

/**
 * 
 * @param Closure $funct [&$value]:void
 * @return void 
 * @throws Error 
 * @throws TypeError 
 */
class SetDirective extends AbstractDirective {
    protected Closure $value;

    function __construct(Closure $funct) {
        $this->setValue($funct);
    }

    public function getValue(&...$args): mixed {
        return call_user_func_array($this->value, $args);
    }

    /**
     * 
     * @param Closure $value 
     * @return void
     * @throws TypeError
     * @throws Error 
     */
    public function setValue(Closure $value): void {
        $funcReflection = new ReflectionFunction($value);
        $argsReflection = $funcReflection->getParameters();
        if(!$argsReflection[0]->isPassedByReference()) {
            throw new Error("The first argument of set() must be accepted by reference.");
        }
        $returnType = $funcReflection->getReturnType();
        if((string)$returnType !== "void") {
            throw new Error("The return type of set() must be explicitly void.");
        }
        $this->value = $value;
    }
}