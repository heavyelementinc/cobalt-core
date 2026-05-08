<?php

namespace Cobalt\Model\Types;

class URLPathType extends StringType {
    function filter($value) {
        if(!$value && $this->hasDirective("fallback")) {
            $value = $this->getDirective("fallback");
        }
        $mutant = url_fragment_sanitize($value);
        return $mutant;
    }
}