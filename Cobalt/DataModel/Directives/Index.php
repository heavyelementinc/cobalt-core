<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Closure;
use Cobalt\DataModel\Directives\Base\AbstractPrototypeMethodDirective;
use Override;

#[Attribute()]
class Index extends AbstractPrototypeMethodDirective {
    
    function __construct(string $method = 'display', array $args = [], readonly bool $sortable = true, readonly int $order = -1, readonly ?string $label = null) {
        return parent::__construct($method, $args);
    }

    function getLabel() {
        return $this->label ?? $this->type->directives?->label?->value ?? ucfirst(from_snake_case($this->type->name));
    }
}