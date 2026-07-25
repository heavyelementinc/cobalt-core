<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Classes\Undefined;
use Cobalt\DataModel\Directives\DefaultValue;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Filters\FilterIssue;
use Override;
use PHPUnit\Util\Filter;

/**
 * The final boss of data types.
 * @package Cobalt\DataModel\Types
 */
class StringType extends Generic {
    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        if($toValidate === null && $this->isNullable($toValidate)) return null;
        if(!is_string($toValidate)) return $this->filterResult->addIssue($this, "Must be a string");
        
        $valid = $this->directives->valid;
        if($valid instanceof Valid) {
            $isEnumeratedValue = false;
            $toValidate = $valid->filter($toValidate, $isEnumeratedValue);
            if($isEnumeratedValue) return $toValidate;
        }

        $len = strlen($toValidate);
        if($this->directives->min?->value && $len < $this->directives->min->value) { 
            $this->filterResult->addIssue($this, sprintf("This text must be at least %d characters long", $this->directives->min->value));
        }
        if($this->directives->max?->value && $len > $this->directives->max->value) {
            $this->filterResult->addIssue($this, sprintf("This text must be no longer than %d characters", $this->directives->max->value));
        }
        
        return $this->filter_pattern($toValidate);
    }

    #[Override]
    public function setValue($mixed):void {
        $this->value = $mixed;
    }
    
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value ?? null;
    }

    public function toUrlFragment():string {
        return url_fragment_sanitize($this->value);
    }

}