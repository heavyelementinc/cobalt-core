<?php

namespace Cobalt\SchemaPrototypes\Traits;

use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;

trait MongoId {
    function isValidIdFormat($value) {
        // if($value instanceof ObjectId) return true;
        if(strlen($value) !== 24) return false;
        if(ctype_xdigit($value)) return true;
        return false;
    }
}