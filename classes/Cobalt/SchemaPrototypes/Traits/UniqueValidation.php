<?php

namespace Cobalt\SchemaPrototypes\Traits;

use Validation\Exceptions\ValidationIssue;

trait UniqueValidation {
    function isUnique($value) {
        $query = [$this->fieldName => $value];
        if($this->ignoreSelf && isset($this->__reference->__dataset['_id'])) $query += ['_id' => ['$ne' => $this->__reference->__dataset['_id']]];
        $count = $this->manager->count($query, ['limit' => 1]);
        if($count === 0) return true;
        return false;
    }
}