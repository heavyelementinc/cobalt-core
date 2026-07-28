<?php

namespace Cobalt\DataModel\Directives\Base;

use Attribute;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Override;

// #[Attribute()]
abstract class AbstractStringDirective extends DirectiveCommon {
    protected string $value;
    
    function __construct(string $value, bool $isMethod = false){
        $this->setValue($value);
        $this->isMethod = $isMethod;
    }

    /**
     * @param string $value 
     * @return void 
     */
    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
    }

    /**
     * @return string
     */
    #[Override]
    public function getValue(): mixed {
        if($this->isMethod) return $this->callModelMethod($this->value, [$this->type->raw]);
        return $this->value;
    }

}