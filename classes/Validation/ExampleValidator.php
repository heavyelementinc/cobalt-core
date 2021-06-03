<?php

/**
 * Example_Validator.php - The Cobalt CRUD Validation Tool
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * This is an example of how to create a validator.
 * 
 * First, consider the form data that will be sent to your Validator. We have 
 * done this with $example_data_needing_validation below.
 *
 * Next, define your schema. This is done by creating a series of methods inside
 * your validator class. Each method's name must match the field name in your
 * schema.
 * 
 * This being the case there are a few limitations on this method of validation.
 * 
 * There are several illegal names that mustn't be used for your schema. These 
 * names include:
 * 
 *    * `__construct`, `__destruct`, `__call`, or any other magic class method
 *    * __allowed_names
 *    * __disallowed_names // Reserved for future use
 *    * __on_validation_complete
 * 
 * If the submitted data has a field that is not allowed, 
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Validation; // Make sure you use the appropriate namespace for your validator 

// If validation fails, throw this exception. The validator will catch these
// exceptions, accumulate the them, and throw an \Exceptions\HTTP\BadRequest
// containing whatever failed messages as the data argument.
use \Validation\Exceptions\ValidationIssue;

class ExampleValidator extends Validate {


    // A list of names that are allowed as part of the final dataset.
    function __get_schema() {
        return [
            'name' => [/* 
                If you do not provide an explicit list of methods then the
                Validate class will try to infer a method by creating a
                method field and store the field name as index 0
            
                'methods' => ['name']
            */],
            'email' => [],
            'phone' => [],
            'region' => [],
            'order_count' => [
                "methods" => [
                    'example_of_using_method_list'
                ],
            ],
        ];
        /**
         * $_POST 
         * 'name'
         * 'email'
         * 'phone'
         * 'region'
         * 'order_count'
         * 'foo' - rejected
         * 'bar' - rejected
         * 'notes' - rejected
         */
    }


    /**
     * This function is called at the end of the validation routine. Its results
     * are array_merged with the validated results.
     *
     * @return array Any data you want to have specified. Is overridden by 
     *               validated results
     */
    function __on_validation_complete($mutant) {
        return [];
    }

    /** Every method will be passed the same args in the same order.
     * 
     *  * The $value of the current field (may have been modified by previous methods)
     *  * The $fieldname is useful for anonymous or global functions where you might
     *    not know the field you're operating on
     *  * The $index is where you are in the list of callables for this field
     * 
     */
    function name($value, $fieldname, $index) {
        return filter_var(trim($value), FILTER_SANITIZE_STRING); // Returning a value set the field name 
    }

    function email($value) {
        return $this->validate_email($value);
    }

    function phone($value) {
        return $this->validate_phone($value);
    }

    function region($value) {
        if (empty($value)) throw new ValidationIssue("Cannot be empty");
        $valid_regions = ['us-east', 'uk-south', 'us-west'];
        foreach ($value as $val) {
            if (!in_array($val, $valid_regions)) throw new ValidationIssue("Invalid region");
        }
        return $value;
    }

    function example_of_using_method_list($value) {
        if (!ctype_digit($value)) throw new ValidationIssue("Must be a digit");
        $value = (int)$value;
        return $value;
    }
}
