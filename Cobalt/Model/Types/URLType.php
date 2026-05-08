<?php

namespace Cobalt\Model\Types;

use Validation\Exceptions\ValidationFailed;

class URLType extends StringType {
    function filter($value) {
        $filtered = filter_var($value, FILTER_VALIDATE_URL);
        if($filtered === false) throw new ValidationFailed("This is not a valid URL");
        return $filtered;
    }
}