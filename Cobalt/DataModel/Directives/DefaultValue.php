<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractMixedDirective;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Interfaces\InheritableDirective;
use Cobalt\DataModel\Types\Generic;
use Override;

#[Attribute()]
class DefaultValue extends AbstractMixedDirective implements InheritableDirective {
    protected string $name = "default";
}