<?php

namespace Cobalt\DataModel\Directives\Filters;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractClosureDirective;
use Cobalt\DataModel\Directives\Base\AbstractStringDirective;
use Override;

/**
 * Instantiation must provide the name of a method on the model.
 * By convention, the name of the method should be <field_name>Filter
 * So if the name of the field is `url`, the filter method should
 * be `urlFilter`
 * */
#[Attribute()]
class Filter extends AbstractStringDirective {

    function __construct(string $value, public array $arguments = [] ) {
        return parent::__construct($value, true);
    }
    #[Override]
    function setValue(mixed $value): void {
        parent::setValue($value, true);
    }
}