<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Traits\Prototype;

class WeakEnumResult extends EnumResult {
    #[Prototype]
    protected function field($type = "select", $misc = []) {
        return $this->inputAutocomplete($misc['class'] ?? "", array_merge($misc, ['allow-custom' => 'true', 'value' => $this->getValue()]));
    }

    function filter($value) {
        return $value;
    }
}