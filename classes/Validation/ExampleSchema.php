<?php

/**
 * ExampleSchema.php - The Cobalt CRUD Validation Tool
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * This is an example of how to create a schema class.
 * 
 * First, consider the data that will be sent to this class. We have done 
 * this with $example_data_needing_validation below.
 *
 * Next, define your schema. This is done by defining each valid fieldname for
 * your schema as a key in the return value of `$this->__get_schema()`
 * 
 * The value of each field _must_ be an array. If the array is empty, then by
 * default, it will have a `get` and `set` key specified and the value will be
 * `get_[fieldname]` and `set_[fieldname]` respectively.
 * 
 * However, do note that the `get` and `set` values can be any callable. That is
 * to say that you can store a string value that is equivalent to a function (or
 * method name accessible through $this context).
 * 
 * The value of `get` and `set` may also be an anonymous function.
 * 
 * 
 * 
 * There are several illegal names that mustn't be used for your schema. These 
 * names include:
 * 
 *    * `__construct`, `__destruct`, `__call`, or any other magic class method
 *    * __schema
 *    * __dataset
 *    * __index
 *    * __normalize_out
 *    * __on_validation_complete
 * 
 * 
 * If the submitted data has a field that is not allowed, the disallowed field
 * will be silently discarded.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Validation; // Make sure you use the appropriate namespace for your validator 

// If validation fails, throw this exception. The __validate method will catch
// these exceptions, accumulate the them, and throw a ValidationFailed exception
// containing whatever failed messages as the data argument.
use \Validation\Exceptions\ValidationIssue;

class ExampleSchema extends Normalize {

    function __construct($data = null, $mode = null) {
        parent::__construct($data, $mode);
    }

    // A list of names that are allowed as part of the final dataset as well as
    // the field's `get` and `set` definitions.
    function __get_schema(): array {
        return [
            'name' => [/* 
                If you do not provide explicit `get` or `set`, a default name
                will be specified. In the case of the `name` field:
                    * `get_name`
                    * `set_name`
                
                Method names will tried to be inferred a method by replacing
                any dots (.) with double underscores (__)
            */],
            'email' => [],
            'phone' => [
                'get' => function ($val, $ct) {
                    return $this->format_phone($val);
                }
            ],
            'region' => [
                'valid' => function ($val, $ct, $name) {
                    return [
                        'us-east' => "US East",
                        'us-west' => "US West",
                        'uk-south' => "UK South"
                    ];
                }
            ],
            'order_count' => [
                'set' => 'example_of_using_set_method'
            ],
            "test" => [
                'set' => function ($val, $ct) {
                    return $this->subdocument($val, [
                        'foo' => [],
                        'bar' => []
                    ]);
                }
            ],
        ];
    }

    /** Every method will be passed the same args in the same order.
     * 
     *  * The $value of the current field (may have been modified by previous methods)
     *  * The $fieldname is useful for anonymous or global functions where you might
     *    not know the field you're operating on
     *  * The $index is where you are in the list of callables for this field
     * 
     */
    public function set_name($value, $fieldname, $index) {
        return filter_var(trim($value), FILTER_SANITIZE_STRING); // Returning a value set the field name 
    }

    public function set_email($value) {
        return $this->validate_email($value);
    }

    public function set_phone($value) {
        return $this->validate_phone($value);
    }

    public function set_region($value) {
        if (empty($value)) throw new ValidationIssue("Cannot be empty");
        $valid_regions = ['us-east', 'uk-south', 'us-west'];
        foreach ($value as $val) {
            if (!in_array($val, $valid_regions)) throw new ValidationIssue("Invalid region");
        }
        return $value;
    }

    public function get_region($value) {
        return array_unique($value);
    }

    public function set_foo($value) {
        $this->required_field($value);
        return $value;
    }

    public function set_bar($value) {
        return $value;
    }

    public function example_of_using_set_method($value) {
        if (!ctype_digit($value)) throw new ValidationIssue("Must be a digit");
        $value = (int)$value;
        return $value;
    }

    /**
     * This function is called at the end of the validation method. Its results
     * are array_merged with the validated results.
     *
     * @return array Any data you want to have specified. Is overridden by 
     *               validated results
     */
    protected function __on_validation_complete($mutant): array {
        return [];
    }
}
