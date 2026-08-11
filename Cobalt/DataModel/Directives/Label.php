<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractStringDirective;
use Override;

#[Attribute()]
class Label extends AbstractStringDirective {
    function __construct(string $label, public ?string $description = null, public ?string $help = null, bool $isMethod = false) {
        return parent::__construct($label, $isMethod);
    }
}