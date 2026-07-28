<?php

namespace Cobalt\Settings\Definitions;

use Cobalt\Settings\CobaltSetting;
use Validation\Exceptions\ValidationIssue;

class LandingPage_route_prefix extends CobaltSetting {

    function filter($value) {
        if(!is_string($value)) throw new ValidationIssue("Must be a string");
        $failed = "";
        if($value[0] !== "/") $failed = "The first character must be a forward slash (/)\n";
        if(substr($value, 0, -1) !== "/") $failed .= "The last character must be a forward slash\n";
        if($failed) throw new ValidationIssue($failed);
        return $value;
    }
}