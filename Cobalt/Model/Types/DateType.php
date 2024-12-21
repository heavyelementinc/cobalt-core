<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Exceptions\DirectiveDefinitionFailure;

class DateType extends MixedType {
    public function initDirectives(): array {
        return [
            'fromEncoding' => 'RFC3339',
            'toEncoding' => 'RFC3339',
        ];
    }

    private function supported_encodings(string $encoding):bool {
        $encodings = ['RFC3339'];
        return in_array($encoding, $encodings);
    }

    #[Directive]
    public function define_fromEncoding(mixed $value, string $name):MixedType {
        if(!$this->supported_encodings($value)) throw new DirectiveDefinitionFailure("$this->name::fromEncoding is not a supported encoding");
        $this->__defineDirective($name, $value);
        return $this;
    }

    #[Directive]
    public function define_toEncoding(mixed $value, string $name):MixedType {
        if(!$this->supported_encodings($value)) throw new DirectiveDefinitionFailure("$this->name::toEncoding is not a supported encoding");
        $this->__defineDirective($name, $value);
        return $this;
    }
}