<?php

namespace cobalt\DataModel\Types\Composite;

use Cobalt\DataModel\Types\StringType;
use Override;

class UrlType extends StringType {
    #[Override]
    function filter(mixed $toValidate, mixed $raw): mixed {
        if(!$toValidate && $this->directives->required?->value ?? false) {
            $this->filterResult->addIssue($this, 'Must be a URL');
        }
        $toValidate = filter_var($toValidate, FILTER_VALIDATE_URL);
        if($toValidate === false) $this->filterResult->addIssue($this, "Must be in a recognized URL format");
        return parent::filter($toValidate, $raw);
    }
}