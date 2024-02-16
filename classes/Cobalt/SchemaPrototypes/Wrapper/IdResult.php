<?php

namespace Cobalt\SchemaPrototypes\Wrapper;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\MongoId;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

class IdResult extends SchemaResult {
    use MongoId;
    protected $type = "_id";
    
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/

    #[Prototype]
    protected function display(): string {
        return (string)$this->value;
    }

    #[Prototype]
    protected function format(): string {
        return (string)$this->value;
    }

    function filter($value) {
        if($value instanceof ObjectId) return $value;
        if(!$this->isValidIdFormat($value)) throw new ValidationIssue("Invalid ID format");
        return new ObjectId($value);
    }
}