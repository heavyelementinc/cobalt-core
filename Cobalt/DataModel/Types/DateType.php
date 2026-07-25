<?php

namespace Cobalt\DataModel\Types;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Override;
use TypeError;

/**
 * @property DateTime $value
 * @package Cobalt\DataModel\Types
 */
class DateType extends Generic {
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return new UTCDateTime($this->value);
    }

    #[Override]
    public function setValue($mixed): void {
        if($mixed instanceof UTCDateTime) $mixed = $mixed->toDateTime();
        if($mixed instanceof DateTime) throw new TypeError("Failed to construct DateTime");
        $this->value = $mixed;
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        if($toValidate instanceof UTCDateTime) $toValidate = $toValidate->toDateTime();
        
        if($toValidate instanceof DateTime) throw new TypeError("Failed to construct DateTime");
        throw new \Exception('Not implemented');
    }

}