<?php

namespace Cobalt\SchemaPrototypes;

class PhoneNumberResult extends StringResult {
    protected $type = "string";

    function validate() {

    }

    function format($format = "(ddd) ddd-dddd") {
        return phone_number_format($this->getValue(), $format);
    }
}