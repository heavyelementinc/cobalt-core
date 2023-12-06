<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Basic\StringResult;
use Validation\Exceptions\ValidationIssue;

class PhoneNumberResult extends StringResult {
    protected $type = "string";

    function format($format = "(ddd) ddd-dddd") {
        return phone_number_format($this->getValue(), $format);
    }

    function filter($value) {
        $match = [];
        $test = preg_match("/(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]‌​)\s*)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)([2-9]1[02-9]‌​|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})\s*(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+)\s*)?$/i", $value, $match);
        if($test === false) throw new ValidationIssue("This does not appear to be a valid phone number. Use the format \"+1 (808) 555-555\" format.");
        return $value;
    }
}