<?php

namespace Cobalt\DataModel\Directives\Base;

use Attribute;
use Closure;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Override;

abstract class AbstractClosureDirective extends DirectiveCommon {
    protected Closure $value;

    function __construct(Closure $value){
        $this->setValue($value);
    }
    /**
     * @param Closure $value 
     * @return void 
     */
    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
        $this->isMethod = true;
    }

    /**
     * @return Closure
     */
    #[Override]
    public function getValue(): mixed {
        return $this->value;
    }
    
    /**
     * @param mixed ...$value
     * @return mixed 
     */
    public function call(mixed ...$value) {
        $funct = $this->value;
        return $funct(...func_get_args());
    }
}