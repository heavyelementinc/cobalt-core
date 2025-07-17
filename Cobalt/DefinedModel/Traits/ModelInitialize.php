<?php

namespace Cobalt\DefinedModel\Traits;

use Cobalt\Model\Types\Interfaces\IMixedType;

trait ModelInitialize {
    public function initializeField(string $fieldName, IMixedType $value): void {
        $this->{$fieldName} = $value;
    }
}