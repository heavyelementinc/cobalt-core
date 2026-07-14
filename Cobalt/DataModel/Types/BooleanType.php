<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Filters\FilterIssue;
use Override;

class BooleanType extends Generic {
    
    #[Override]
    public function setValue($mixed):void {
        $this->value = filter_var($mixed, FILTER_VALIDATE_BOOL);
    }

    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value ?? null;
    }

    #[Override]
    public function filter(mixed $toValidate): mixed {
        if($toValidate === null && $this->isNullable($toValidate)) return null;
        if(is_bool($toValidate)) return $toValidate;
        $toValidate = filter_var($toValidate, FILTER_VALIDATE_BOOL);
        
        $valid = $this->directives->valid;
        if($valid instanceof Valid) {
            $allowed = $valid->getValue();
            if(!in_array($toValidate, $allowed)) {
                $this->filterResult->addIssue($this, "Must be ". json_encode($allowed));
            }
        }
        return $toValidate;
    }

    #[Override]
    function getValidComparisonValues(): ?array {
        return [json_encode($this->getValue())];
    }
}