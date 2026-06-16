<?php

namespace Cobalt\Commands\Attributes;
use \Attribute;

#[Attribute]
class LongDescription {
    public function __construct(public string $description){}
}