<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractBoolDirective;
use Override;

#[Attribute()]
class PrivateValue extends AbstractBoolDirective {
    protected string $name = "private";
}