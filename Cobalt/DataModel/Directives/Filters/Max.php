<?php

namespace Cobalt\DataModel\Directives\Filters;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractNumberDirective;
use Override;

/**
 * Sets a maximum value for a field
 *  * StringType - sets the max length of the string
 *  * ArrayType  - sets the max elements in the array
 *  * NumberType - sets the upper bound of the number
 * 
 * Provides the max=<value> attribute for fields()
 * @package Cobalt\DataModel\Directives
 */
#[Attribute()]
class Max extends AbstractNumberDirective {
    // use FilterableDirective;

    // #[Override]
    // public function filter(mixed $toValidate): mixed {
    //     if($toValidate === $this->getValue())
    // }
}