<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Traits\Defineable;

class ArrayType extends MixedType {
    use Defineable;
    public function setValue($array):void {
        $this->value = [];
        foreach($array as $index => $value) {
            $this->define($this->value, $index, $value, null, $this->model, $this->name."[".$index."]");
        }
        $this->isSet = true;
    }

    public function __toString(): string {
        return join(", ", $this->getValue());
    }
}