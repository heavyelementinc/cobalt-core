<?php
namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;

class PhoneNumberType extends MixedType {
    
    function display(): mixed {
        return $this->format();
    }

}