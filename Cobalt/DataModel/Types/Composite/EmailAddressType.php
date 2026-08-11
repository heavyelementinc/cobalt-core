<?php

namespace Cobalt\DataModel\Types\Composite;

use Cobalt\DataModel\Types\StringType;
use Override;

class EmailAddressType extends StringType {
    #[Override]
    function filter(mixed $toValidate): mixed {
        if(filter_var($toValidate, FILTER_FLAG_EMAIL_UNICODE) === false) {
            $this->filterResult->addIssue($this, "This does not appear to be a recognized email format");
        }
        return parent::filter($toValidate);
    }
}