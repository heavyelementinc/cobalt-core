<?php

namespace Cobalt\Commands\Attributes;
use \Attribute;

/**
 * CommandMethod must be specified for all command methods
 * Methods must return an int.
 * @package Cobalt\Commands\Attributes
 */
#[Attribute]
class CommandMethod {
}