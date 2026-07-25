<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Exceptions\ImmutableTypeError;
use Validation\Exceptions\ValidationIssue;

/** @package Cobalt\Model\Types */
class BinaryType extends MixedType
{
    protected string $type = "binary";

    public function setValue($value):void {
        if($this->isSet && $this->directiveOrNull(DIRECTIVE_KEY_IMMUTABLE)) throw new ImmutableTypeError("This value is considered immutable and must not be changed.");
        $this->value = $value;
        $this->isSet = true;
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null): string
    {
        if ($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if ($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-date";
        if ($tag === null) $tag = "input-date";
        return $this->inputBinary($class, $misc, $tag);
    }

    public function filter($value) {
        if(!is_numeric($value)) throw new ValidationIssue("Must be an integer value");
        $valid = $this->getDirective("valid");
        $max = max(...array_keys($valid));
        $i = 0;
        while(true) {
            $bit = 0b001 << $i;
            if(!key_exists($bit, $valid)) {
                if($value & $bit = $bit) throw new ValidationIssue("$bit is not a valid flag");
            }
            if($max >> $i >= 0) break;
        }
        // if($value > $total) throw new ValidationIssue("Value is too high");
        if($value < 0) throw new ValidationIssue("Value must not be negative");
        return $value;
    }

    /**
     * Each child of MixedType should return an appropriately typecast
     * version of the $value parameter
     * @param mixed $value 
     * @return mixed 
     */
    public function typecast($value, $type = QUERY_TYPE_CAST_LOOKUP)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    public function and($value)
    {
        return $this->value & $value;
    }

    public function or($value)
    {
        return $this->value | $value;
    }

    public function xor($value)
    {
        return $this->value ^ $value;
    }

    public function not() {
        return ~$this->value;
    }

    public function left($places = 1) {
        return $this->value << $places;
    }

    public function right($places = 1) {
        return $this->value >> $places;
    }

    public function includes($value) {
        return $this->and($value) == $value;
    }

    public function set(int $flags) {
        $this->value |= $flags;
    }
    public function unset(int $flags) {
        $this->value ^= $flags;
    }
}
