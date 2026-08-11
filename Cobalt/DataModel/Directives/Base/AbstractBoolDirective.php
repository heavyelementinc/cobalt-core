<?php

namespace Cobalt\DataModel\Directives\Base;

use Attribute;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Override;

// #[Attribute()]
abstract class AbstractBoolDirective extends DirectiveCommon {
    protected bool|string $value;

    function __construct(bool|string $value = true){
        $this->setValue($value);
    }
    /**
     * @param bool $value 
     * @return void 
     */
    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
        $this->isMethod = is_string($value);
    }

    /**
     * @return bool
     */
    #[Override]
    public function getValue(): mixed {
        if($this->isMethod) return $this->callModelMethod($this->value, [$this->type->raw]);
        return $this->value;
    }
    
}