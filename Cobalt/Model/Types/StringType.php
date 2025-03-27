<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Attributes\Prototype;
use Validation\Exceptions\ValidationIssue;

/**
 * * Filters
 * @package Cobalt\Model\Types
 */
class StringType extends MixedType {

    function filter($value) {
        $value = parent::filter($value);
        $this->character_limit($value);
        $this->illegal_chars($value);
        $this->str_replace($value);
        return $value;
    }

    function character_limit($value) {
        // Get our subject's length
        $length = strlen($value);
        $min = null;
        if($this->hasDirective('min')) $min = $this->getDirective("min");
        if(is_null($min)) $min = 0;
        if($length < $min) throw new ValidationIssue("This value must be at least $min characters");
        $max = null;
        if($this->hasDirective("max")) $max = $this->getDirective("max");
        if(is_null($max)) return $value;
        if($length <= $max) return $value;
        throw new ValidationIssue("This value may not be greater than $max characters");
    }
    
    function illegal_chars($value) {
        if(!$this->hasDirective("illegal_chars")) return $value;
        // Split our string of illegal characters into an array
        $illegal = str_split($this->getDirective('illegal_chars'));
        // Remove any illegal characters from the subject
        $mutant = str_replace($illegal, "", $value);
        // Compare the subject and the mutated value. If they don't match then
        // the subject must have contained illegal characters
        if($mutant !== $value) throw new ValidationIssue("This entry contains illegal characters.");
        return $value;
    }

    function str_replace($value) {
        if(!$this->hasDirective('str_replace')) return $value;
        $directive = $this->getDirective();
        return str_replace($directive[0], $directive[1], $value);
    }

    #[Directive()]
    public function defineCharacterLimit(int $value):StringType {
        $this->__defineDirective('character_limit', $value);
        return $this;
    }

    #[Directive()]
    public function defineIllegalChars(string $value):StringType {
        $this->__defineDirective('illegal_chars', $value);
        return $this;
    }

    #[Directive()]
    public function defineStrReplace(string|array $replaced_chars, string|array $replace_with):StringType {
        $this->__defineDirective('str_replace', [$replaced_chars, $replace_with]);
        return $this;
    }
    
    #[Prototype]
    protected function md() {
        $val = $this->value;
        $asHtml = $this->asHTML;
        if ($this->schema['md_preserve_tags'] === true) $asHtml = true;
        return from_markdown($val, $asHtml ?? false);
    }

    #[Prototype]
    protected function length():int|null {
        return strlen($this->value);
    }

    #[Prototype]
    protected function capitalize(){
        return ucfirst($this->value);
    }

    #[Prototype]
    protected function uppercase() {
        return strtoupper($this->value);
    }

    #[Prototype]
    protected function toUppercase() {
        return $this->uppercase();
    }

    #[Prototype]
    protected function toUpper() {
        return $this->uppercase();
    }

    #[Prototype]
    protected function lowercase() {
        return strtolower($this->value);
    }

    #[Prototype]
    protected function toLowercase() {
        return $this->lowercase();
    }

    #[Prototype]
    protected function toLower() {
        return $this->lowercase();
    }

    #[Prototype]
    protected function last() {
        return $this->value[count($this->value) - 1];
    }

    #[Prototype]
    protected function resverse() {
        return strrev($this->value);
    }

    #[Prototype]
    protected function substring(string $start, ?string $length = null, array $options = []) {
        return substr($this->value, $start, $length);
    }
}