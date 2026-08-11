<?php

namespace Cobalt\DataModel\Directives\Filters\Arrays;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractMixedDirective;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Types\Generic;
use Override;

#[Attribute()]
class Each extends AbstractMixedDirective {
    protected string $name = "each";

    function __construct(Generic|string $generic, bool $isMethod = false) {
        parent::__construct($generic, $isMethod);
    }
}