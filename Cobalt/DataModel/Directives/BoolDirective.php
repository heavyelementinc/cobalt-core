<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractBoolDirective;
use Override;

#[Attribute()]
class BoolDirective extends AbstractBoolDirective {
    #[Override]
    function __construct(string $name, bool|string $value){
        return parent::__construct($value);
    }
}