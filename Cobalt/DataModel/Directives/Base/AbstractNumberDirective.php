<?php

namespace Cobalt\DataModel\Directives\Base;

use Attribute;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Override;

// #[Attribute()]
abstract class AbstractNumberDirective extends AbstractMixedDirective {
    protected mixed $value;
    
    function __construct(mixed $value){
        parent::__construct($value, is_string($value));
    }

    /**
     * @param string $value 
     * @return void 
     */
    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
    }

    #[Override]
    public function getValue(): mixed {
        return parent::getValue();
    }

}