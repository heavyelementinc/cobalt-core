<?php

namespace Cobalt\DataModel\Types;

use Override;

class EnumType extends Generic {
    
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value;
    }

    #[Override]
    public function setValue($mixed): void {
        $this->value;
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        return parent::filter($toValidate, $raw);
    }

}