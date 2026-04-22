<?php

use Cobalt\Model\Attributes\Prototype;

namespace Cobalt\Model\Types;

class PhoneNumberType extends MixedType {
    
    protected function display() {
        return $this->format();
    }

}