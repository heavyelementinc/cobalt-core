<?php

namespace Cobalt\Model\Types;

use Error;

class RadioType extends EnumType {
    function field(string $class = "", array $misc = [], ?string $tag = null): string {
        return $this->select("", [], "input-radio");
    }
}