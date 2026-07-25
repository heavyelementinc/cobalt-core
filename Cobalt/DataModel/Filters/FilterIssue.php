<?php

namespace Cobalt\DataModel\Filters;

use Cobalt\DataModel\Types\Generic;
use Throwable;
use Override;
use TypeError;

class FilterIssue extends TypeError {
    
    function __construct(
        public readonly Generic $generic,
        public readonly string $publicMessage = "",
        public readonly ?string $privateMessage = null,
        $code = 0)
    {
        parent::__construct($this->privateMessage ?? $this->publicMessage, $this->code);
    }
}