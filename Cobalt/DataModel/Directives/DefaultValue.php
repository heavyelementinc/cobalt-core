<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractMixedDirective;
use Override;

#[Attribute()]
class DefaultValue extends AbstractMixedDirective {
    protected string $name = "default";
}