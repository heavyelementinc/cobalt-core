<?php

namespace Cobalt\Model\Types;

use Override;

class WeakEnumType extends EnumType {
    #[Override]
    function field(string $class = "", array $misc = [], ?string $tag = null): string {
        $misc += [
            'type' => 'weak',
            'value' => $this->value
        ];
        return parent::field($class, $misc, "input-autocomplete");
    }
}