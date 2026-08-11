<?php

namespace Cobalt\DataModel\Types;

use Exception;
use MongoDB\BSON\ObjectId;
use Override;
use Throwable;

class IdType extends Generic {
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value;
    }

    #[Override]
    public function setValue($mixed): void {
        if(is_string($mixed)) $mixed = new ObjectId($mixed);
        $this->value = $mixed;
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        $toValidate = self::toObjectId($toValidate);
        if(is_string($toValidate)) {
            $this->filterResult->addIssue($this, $toValidate);
        }
        return $toValidate;
    }

    #[Override]
    public function getValidComparisonValues(): ?array {
        return [(string)$this->value];
    }

    #[Override]
    function jsonSerialize(): mixed {
        return (string)$this->value;
    }

    static function toObjectId(mixed $toValidate):ObjectId|string {
        if($toValidate instanceof ObjectId) return $toValidate;
        if(!is_string($toValidate)) return "Must be an object ID or string";
        if(strlen($toValidate) !== 24) return "Incorrect length";
        if(preg_match("/^[0-9][a-fA-F\d]{24}$/",$toValidate)) return "Contains illegal characters";
        try {
            return new ObjectId($toValidate);
        } catch (Throwable $e) {
            return "An unknown error occurred";
        }
    }
}