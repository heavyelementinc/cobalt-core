<?php

namespace Cobalt\DataModel\Types;

use Override;
use TypeError;

class BinaryType extends Generic {
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value;
    }

    #[Override]
    public function setValue($mixed): void {
        if(!is_int($mixed)) throw new TypeError("BinaryType must be an integer");
        $this->value = $mixed;
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        if(!is_int($toValidate)) {
            throw $this->filterResult->addIssue($this, "Binary values must supplied with an `int`");
        }
        $this->filter_pattern($toValidate);
        $this->isNullable($toValidate);
        $min = $this->directives->min?->value ?? null;
        $max = $this->directives->min?->value ?? null;
        if($min && $max && $min > $max) return $this->filterResult->addIssue($this, "Impossible filter constraints", "Min/max impossibilitiy. Max is smaller than min");
        if($min && $toValidate < $min) $this->filterResult->addIssue($this, "Value too low (must be no lower than $min)");
        if($max && $toValidate > $max) $this->filterResult->addIssue($this, "Value too high (must be no greater than $max)");
        return $toValidate;
    }

}