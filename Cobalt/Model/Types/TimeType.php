<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Validation\Exceptions\ValidationIssue;

class TimeType extends MixedType {
    const PATTERN = "/^[0-2][0-9]:[0-5][0-9]$/";
    function filter($value) {
        $matched = preg_match(self::PATTERN, $value);
        if(!$matched == false) throw new ValidationIssue("Must be in 24-hour time format'");
        return $value;
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        return parent::field(
            $class, 
            [
                'type' => 'time',
                ...$misc
            ], 
            $tag ?? "input"
        );
    }

}