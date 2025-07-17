<?php

namespace Cobalt\DefinedModel\Traits;

use Cobalt\Model\Types\MixedType;
use TypeError;

trait ModelOverloading {
    public function __set(string $fieldName, mixed $value) {
        if(property_exists($this, $fieldName)) {
            if($this->{$fieldName} instanceof MixedType === false) throw new TypeError("Field $fieldName is not modifiable");
            $this->{$fieldName}->setValue($value);
            return;
        }
        $this->{$fieldName} = MixedType::typeFromValue($value, $fieldName);
        // Let's update our iterator_index
        array_push($this->iterator_index, $fieldName);
    }

    public function __get(string $fieldName):mixed {
        if(property_exists($this, $fieldName) && $this->{$fieldName} instanceof MixedType) {
            return $this->{$fieldName};
        }

        return null;
    }

    public function __isset($fieldName) {
        if(property_exists($this, $fieldName) && $this->{$fieldName} instanceof MixedType) {
            return true;
        }

        return false;
    }
}