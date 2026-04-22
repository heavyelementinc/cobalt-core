<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Validation\Exceptions\ValidationIssue;

class EmailAddressType extends StringType {
    function filter($value) {
        $value = parent::filter($value);
        if(filter_var($value, FILTER_VALIDATE_EMAIL) == false) throw new ValidationIssue("This does not appear to be a valid email address.");
        return $value;
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null): string {
        return $this->input("", ['type' => 'email'], "input");
    }

    protected function getUsername():string {
        return explode("@", $this->value)[0];
    }

    protected function getHostname():string {
        return explode("@", $this->value)[1];
    }

    protected function hasTag() {
        return (explode("+", $this->getUsername())[1]) ? true : false;
    }
}