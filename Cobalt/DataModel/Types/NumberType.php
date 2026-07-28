<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Attributes\PrototypeMethod;
use Cobalt\DataModel\Classes\Undefined;
use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Filters\FilterIssue;
use Override;
use TypeError;

class NumberType extends Generic {
    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        if(!is_numeric($toValidate)) throw $this->filterResult->addIssue($this, "This field requires a numeric value.");
        $toValidate = $this->filter_pattern($toValidate);
        $min = $this->directives->min;
        $max = $this->directives->max;
                
        if($min instanceof Min && $toValidate < $min->getValue()) {
            $this->filterResult->addIssue($this, sprintf("Value must be greater than or equal to %d",$min->getValue()));
        }
        if($max instanceof Max && $toValidate > $max->getValue()) {
            $this->filterResult->addIssue($this, sprintf("Value must be less than or equal to %d", $max->getValue()));
        }
        return $toValidate;
    }
    
    #[Override]
    public function setValue($mixed):void {
        if(!is_numeric($mixed)) throw new TypeError("Value `$mixed` is not numeric");
        $this->value = $mixed;
    }

    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value ?? new Undefined();
    }

    #[Override]
    function getValidComparisonValues(): ?array {
        return [$this->getValue()];
    }

    #[PrototypeMethod()]
    protected function add(int|float|NumberType $number) {
        $this->value += $number->value ?? $number;
        return $this;
    }

    #[PrototypeMethod()]
    protected function subtract(int|float|NumberType $number) {
        $this->value -= $number->value ?? $number;
        return $this;
    }

    #[PrototypeMethod()]
    protected function multiply(int|float|NumberType $number) {
        $this->value *= $number->value ?? $number;
        return $this;
    }

    #[PrototypeMethod()]
    protected function divide(int|float|NumberType $number) {
        $number = $number->value ?? $number;
        if($number === 0) throw new TypeError("Cannot divide by zero");
        $this->value /= $number;
        return $this;
    }

    #[PrototypeMethod()]
    protected function inc(int|float|NumberType $by) {
        return $this->add($by);
    }

    #[PrototypeMethod()]
    protected function dec(int|float|NumberType $by) {
        return $this->subtract($by);
    }

    #[PrototypeMethod()]
    protected function modulo(int|float|NumberType $by) {
        $this->value %= $by->value ?? $by;
        return $this;
    }

    #[PrototypeMethod()]
    protected function exponent(int|float|NumberType $by) {
        $this->value **= $by->value ?? $by;
        return $this;
    }

    #[PrototypeMethod()]
    protected function abs() {
        $this->value = abs($this->value);
        return $this;
    }

    #[PrototypeMethod()]
    protected function negate() {
        $this->value *= -1;
        return $this;
    }
}