<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractStringDirective;
use Override;

#[Attribute()]
class StringDirective extends AbstractStringDirective {
    #[Override]
    function __construct(protected string $name, string $value, bool $isMethod = false)
    {
        return parent::__construct($value, $isMethod);
    }
}