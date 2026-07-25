<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractStringDirective;
use Override;

#[Attribute()]
class Fieldset extends AbstractStringDirective {
    function __construct(string $label, bool $isMethod = false) {
        return parent::__construct($label, $isMethod);
    }
}