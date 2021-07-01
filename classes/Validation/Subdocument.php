<?php

namespace Validation;

use Validation\Exceptions\SubdocumentValidationFailed;
use Validation\Exceptions\ValidationFailed;

class Subdocument extends Normalize {

    function __construct($values, $schema) {
        parent::__construct($values);
        $this->init_schema($schema);
    }

    public function __get_schema(): array {
        return [];
    }

    public function __validate($data) {
        $mutant = [];
        $issues = [];
        foreach ($data as $index => $val) {
            try {
                $result = parent::__validate($val);
                $mutant[$index] = $result;
            } catch (ValidationFailed $e) {
                foreach ($e->data as $key => $value) {
                    $issues["$index.$key"] = $value;
                }
            }
        }
        if (count($issues)) throw new SubdocumentValidationFailed($issues);
        return $mutant;
    }
}
