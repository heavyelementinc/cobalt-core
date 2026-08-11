<?php

namespace Cobalt\DataModel\Directives\Filters;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractNumberDirective;

/**
 * Sets a minimum value for a field
 *  * StringType - sets the min length of the string
 *  * ArrayType  - sets the min elements in the array
 *  * NumberType - sets the upper bound of the number
 *  * BinaryType - sets the upper bound of a binary int
 * 
 * Min values should be checked inclusively
 * 
 * Provides the max=<value> attribute for fields()
 * @package Cobalt\DataModel\Directives
 */
#[Attribute()]
class Min extends AbstractNumberDirective {
}