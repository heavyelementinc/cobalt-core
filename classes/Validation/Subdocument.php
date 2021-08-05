<?php

namespace Validation;

use Validation\Exceptions\SubdocumentValidationFailed;
use Validation\Exceptions\ValidationFailed;

class Subdocument extends Normalize {

    function __construct($values, $schema, &$parent) {
        $this->parent = $parent;
        parent::__construct([]);
        //if (gettype($values) === "object") $values = json_decode(json_encode($values));
        $this->__original_dataset = array_merge($this->__dataset, $values);
        $this->init_schema($schema);
    }

    public function __get_schema(): array {
        return [];
    }

    public function __validate($data) {
        // Keep track of our mutated values
        $mutant = [];
        // Keep track of any issues with data
        $issues = [];
        // Loop through each document
        foreach ($data as $index => $val) {
            // Set the dataset to the current document
            $this->__dataset = $val;
            try {
                // Validate this document
                $result = parent::__validate($val);
                // Store the result in $mutant
                $mutant[$index] = $result;
            } catch (ValidationFailed $e) {
                // Catch any ValidationFailed errors and build an indexed
                // array of issues
                foreach ($e->data as $key => $value) {
                    $issues["$index.$key"] = $value;
                }
            }
        }
        // Check if validation failed and throw a SubdocFailed
        if (count($issues)) throw new SubdocumentValidationFailed($issues);
        // Set this instances's dataset to $mutant 
        $this->__dataset = $mutant;
        return $mutant; // Return mutant
    }

    protected function __modify($field, $value) {
        // Update the original, hopefully.
        $this->parent->__dataset[$field] = $value;

        // So we can access these later from within this context if need be
        $this->__dataset[$field] = $value;
    }
}
