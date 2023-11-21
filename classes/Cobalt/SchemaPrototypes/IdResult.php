<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\SchemaPrototypes\Traits\MongoId;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;

class IdResult extends SchemaResult {
    use MongoId;
    protected $type = "_id";
    
    public function display(): string {
        return (string)$this->value;
    }

    public function format(): string {
        return (string)$this->value;
    }

    function filter($value) {
        if($value instanceof ObjectId) return $value;
        if(!$this->isValidIdFormat($value)) throw new ValidationIssue("Invalid ID format");
        return new ObjectId($value);
    }
}