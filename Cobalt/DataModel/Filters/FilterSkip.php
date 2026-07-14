<?php

namespace Cobalt\DataModel\Filters;

use Cobalt\DataModel\Types\Generic;

class FilterSkip {
    function __construct(
        public readonly Generic $generic,
        public readonly string $reason) {
        
    }
}