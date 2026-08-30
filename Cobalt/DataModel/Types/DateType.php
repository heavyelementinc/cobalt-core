<?php

namespace Cobalt\DataModel\Types;

use DateTime;
use DateTimeZone;
use MongoDB\BSON\UTCDateTime;
use Override;
use TypeError;

/**
 * @property DateTime $value
 * @package Cobalt\DataModel\Types
 */
class DateType extends Generic {
    const FORMAT_W3C = \DateTimeInterface::W3C;
    const FORMAT_DT_LOCAL = DATETIME_LOCAL_FORMAT;
    const FORMAT_INPUT = "Y-m-d";
    const DATE = "Y-m-d";
    const TIME = "H:i";
    const FORMAT_RFC3339 = 'c';
    const FORMAT_ISO = 'c';
    const FORMAT_DEFAULT = 'm/d/Y';
    const FORMAT_VERBOSE = "l, F jS Y g:i A";
    const FORMAT_NO_DOW = "F jS Y g:i A";
    const FORMAT_LONG = "l, F jS Y";
    const FORMAT_12_HOUR = "g:i a";
    const FORMAT_24_HOUR = "H:i";
    const FORMAT_SECONDS = "g:i:s A";

    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return new UTCDateTime($this->value);
    }

    #[Override]
    public function setValue($mixed): void {
        if($mixed instanceof UTCDateTime) $mixed = $mixed->toDateTime();
        if(is_string($mixed)) $mixed = new DateTime($mixed);
        // if($mixed instanceof DateTime)
        $this->value = $mixed;
        $this->value->setTimezone(new DateTimeZone($_SESSION['timezone'] ?? config()['timezone']));
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        if($toValidate instanceof UTCDateTime) $toValidate = $toValidate->toDateTime();
        
        if(is_string($toValidate)) return new DateTime($toValidate);
        if($toValidate instanceof DateTime) return $toValidate;
        $this->filterResult->addIssue($this,"Failed to evaluate ".gettype($toValidate)." as valid DateTime");
        return $toValidate;
    }

    public function format(string $format = self::FORMAT_DT_LOCAL):string {
        $value = $this->getValue();
        if(!$value) return "";
        return $value->format($format);
    }
}