<?php

/**
 * The ImmutableResult is a field that cannot be updated. This is generally
 * used for fake fields in a PersistanceMap that draw their values from other
 * fields or from a default value in the schema.
 * 
 * Generally, the 'get' schema directive should be defined as a function.
 * 
 * Alternatively, you can define a 'default' schema directive.
 * 
 * By default, validating this field will silently fail and any data will be 
 * lost. If this is undesireable, set the 'continueOnFilter' to `false` and
 * the a ValidationIssue will be thrown when this field is filtered().
 * 
 * @package Cobalt\SchemaPrototypes
 * @author Gardiner Bryant, Heavy Element
 * @copyright 2023 Heavy Element
 */

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationIssue;

// use Validation\Exceptions\ValidationIssue;

class ImmutableResult extends SchemaResult {

    function defaultSchemaValues(array $data = []): array {
        return array_merge([
            ['continueOnFilter' => true]
        ],parent::defaultSchemaValues($data));
    }

    function filter($val) {
        if($this->schema['continueOnFilter']) throw new ValidationContinue("$this->name is a read-only field");
        throw new ValidationIssue("'$this->name' field is immutable");
    }

}