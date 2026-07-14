<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractBoolDirective;
use Cobalt\DataModel\Directives\Base\AbstractMixedDirective;
use Override;

#[Attribute()]
class AllowOverloading extends AbstractBoolDirective {
    protected string $name = "allow_overloading";

    function __construct(bool|string $value = false){
        $this->setValue($value);
    }
}