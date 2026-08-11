<?php

namespace Cobalt\DataModel\Directives\Filters;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractBoolDirective;

/**
 * The field() method will append a button that resets this
 * field's value to null
 * @package Cobalt\DataModel\Directives
 */
#[Attribute()]
class Clearable extends AbstractBoolDirective {
    
}