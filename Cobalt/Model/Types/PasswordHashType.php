<?php

namespace Cobalt\Model\Types;

class PasswordHashType extends MixedType {
    function field(string $class = "", array $misc = [], ?string $tag = null): string
    {
        $misc['value'] = "";
        return parent::field($class, $misc, "input-password" ?? $tag);
    }
}