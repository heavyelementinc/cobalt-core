<?php

namespace Cobalt\SchemaPrototypes\Traits;

use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;

trait BuildUpdate {
    function __getUpdateCommand($data, $validate = false):array {
        if($validate) $data = $this->validate($data);
        $update = [];

        if($this->__schema) {

        }

        return $update;
    }

    function __getFieldParams($fieldname, $value) {

    }
}
