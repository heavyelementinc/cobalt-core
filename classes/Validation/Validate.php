<?php

/**
 * Validate.php - The Cobalt CRUD Validation Class
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * This class is meant to provide a consistent method of validation for data 
 * across Cobalt engine and any project based on it.
 * 
 *  > $process = new \Example\ExampleValidate(); // Extends this class
 *  > $result = $process->validate($data_to_be_validated);
 * 
 * Setting up your schema
 * ======================
 * 
 * Your schema is used to provide incoming data with a list of validation
 * instructions. In your implementation of __get_schema(), you're required to
 * provide these instructions.
 * 
 * For example, if we have a field named `phone` and we wanted to validate that
 * it was in the expected format, we could do:
 * 
 * ```php
 * function __get_schema(){
 *      return [
 *          'phone' => [
 *              'methods' => ['validate_phone']
 *          ]
 *      ]
 * }
 * ```
 * 
 * > NOTE: the 'methods' field should be an indexed array of either callables
 * within $this context, global named functions, or anonymous functions.
 * 
 * If you _do not specify_ a 'methods' array, the Validator will check if $this
 * has a method which matches the fieldname and if it does, it will create the
 * 'methods' array and add the fieldname to the 'methods' array.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Validation;

use Exception;
use \Exceptions\HTTP\BadRequest;
use \Validation\Exceptions\ValidationFailed;
use \Validation\Exceptions\ValidationIssue;

abstract class Validate {
    function __construct() {
    }

    /** Returns an associative array which acts as a schema of allowed field names
     * 
     * $example = [ 'product_object' => [
     * 
     *     'methods' => [
     * 
     *       'object', // A string equivalent to the name of the method to be run
     * 
     *       function ($value, $fieldname, $index) { // An anonymous function
     * 
     *           return $value;
     * 
     *       }
     * ]]];
     * 
     * Methods and functions run by the validator will provide the following
     * parameters when calling:
     *  * $value - the current value of the fieldname (may have been modified
     *              by previous methods in the list)
     *  * $fieldname - the value of the current fieldname
     *  * $index - the index into the methods list
     * 
     * @return array an associative with a list of methods for each field
     */
    abstract function __get_schema();

    /**
     * Returns a subset (possibly all) of $to_validate with their values having
     * been validated by the routine.
     * 
     * @param array $to_validate 
     * @return array validated subset of $to_validate 
     */
    final function __validate(array $to_validate) {
        $this->__to_validate = $to_validate;

        // Get a subset of allowed fieldnames from the submitted data
        $subset = $this->get_subset();
        if (count($subset) <= 0) throw new BadRequest("No valid data submitted");

        $mutant = []; // Establish our mutant
        $problems = []; // Establish our problems container

        /** We have a $problems container so that we can run through our entire
         * list of items to be validated, find _all_ issues, then send those 
         * issues back to the client so they can be fixed.
         */

        foreach ($subset as $fieldname => $value) {
            // Check if methods are specified for this fieldname
            if (!key_exists("methods", $this->__schema[$fieldname])) {
                if (!method_exists($this, $fieldname)) throw new \Exception("$fieldname does not have a validator method");
                $this->__schema[$fieldname]['methods'] = [$fieldname];
            }

            // Add the $value to the $mutant so we can update it through each
            // iteration in the list of methods.
            $mutant[$fieldname] = $value;

            // Loop through the available validation methods
            foreach ($this->__schema[$fieldname]['methods'] as $index => $callable) {
                try {
                    // Execute the method
                    $mutant[$fieldname] = $this->execute_method($callable, $mutant[$fieldname], $fieldname, $index);
                } catch (ValidationIssue $e) {
                    // Add fieldnames to the $problems array
                    if (!isset($problems[$fieldname])) $problems[$fieldname] = $e->getMessage();
                    else $problems[$fieldname] .= "\n" . $e->getMessage();
                }
            }
        }

        /* The only reason we would have a count of $problems other than 0 is if
           there were ValidationProblems thrown. */
        if (count($problems) !== 0) throw new ValidationFailed("Validation failed.", $problems);
        /** Check if we have an __on_validation_complete method and, if so,
         * array_merge $mutant with the results of __on_validation_complete;
         */
        return array_merge($mutant, $this->{"__on_validation_complete"}($mutant));
    }

    final function validate(array $to_validate) {
        return $this->__validate($to_validate);
    }

    function get_subset() {
        // Get the schema from the abstract class and do type checking
        $this->__schema = $this->__get_schema();
        if (gettype($this->__schema) !== "array") throw new \Exception("Invalid");

        // Create a subset of allowed fields from $this->__to_validate
        $subset = [];
        foreach ($this->__schema as $key => $validate) {
            if (key_exists($key, $this->__to_validate)) $subset[$key] = $this->__to_validate[$key];
        }

        return $subset;
    }

    function execute_method($callable, $value, $fieldname, $index) {
        // Covers methods in $this class and extensions
        if (method_exists($this, $callable)) return $this->{$callable}($value, $fieldname, $index);
        // Covers strings that match the name of a callable and anonymous functions
        if (is_callable($callable)) return $callable($value, $fieldname, $index);
    }

    /** Upon successful validation, this method is called and the return values
     * are merged with the mutant.
     * 
     * @return array
     */
    function __on_validation_complete($mutant) {
        return [];
    }

    /* ============================== */
    /*        HELPER FUNCTIONS        */
    /* ============================== */

    /**
     * Validates an email address
     * 
     * Trims and email and validates its formatting using filter_var
     * 
     * 
     * @param string $value 
     * @return string validated email address
     * @throws ValidationIssue upon failed validation
     */
    final function validate_email(string $value) {
        $value = trim($value);
        if (!\filter_var($value, FILTER_VALIDATE_EMAIL)) throw new ValidationIssue("Malformed email");
        return strtolower($value); // We can return here because we know we have a valid email
    }

    /**
     * Validate a phone number and removes junk characters: () -
     * 
     * Removes junk characters /[()-\s]/ and checks if the resulting string contains
     * only digits.
     * 
     * @param string $value the phone number to be evaluated
     * @param int $min_length the minimum number of characters the string should be
     * @return string validated string of digits
     * @throws ValidationIssue 
     */
    final function validate_phone($value, $min_length = 10) {
        $value = phone_number_normalize($value);

        // Check if the phone number is only digits and if not throw an exception.
        if (!ctype_digit($value)) throw new ValidationIssue("Malformed phone number");

        if (strlen($value) < $min_length) throw new ValidationIssue("Not long enough");

        return $value;
    }

    /**
     * Fails if most falsey values are found
     * 
     * **NOTE** that values *0, 0.0,* and *"0"* are allowed but other falsey
     * values will result in a failure of this check.
     * 
     * Boolean `false` is, by default, considered a value.
     * 
     * @param mixed $value the value to check if empty
     * @param bool $allow_false [true] bool false should be considered a value
     * @return mixed $value does not modify the value
     * @throws ValidationIssue 
     */
    final function required_field($value, $allow_false = true) {
        if ($allow_false && $value === false) return $value;
        if (empty($value) && !is_numeric($value)) throw new ValidationIssue("This field is required");
        return $value;
    }

    /**
     * Test if $value and a comparison field are empty and raise issue.
     * 
     * Meant to be used when one or another field are required to have a value.
     * For example, if a form has a phone number or email and one is required.
     * If both fields are empty then a ValidationIssue will be thrown.
     * 
     * > Note that a 0 value 
     * 
     * @param mixed $value the value of the current field
     * @param mixed $other_field the other field name to test
     * @param string $message 
     * @param bool $allow_false [true] if false should be considered a value
     * @return mixed $value this method does not modify $value
     * @throws Exception if $other_field not found in __to_validate
     * @throws ValidationIssue if both values are considered empty
     */
    final function one_required($value, $other_field, $message = "One of these fields needs to be specified", $allow_false = true) {
        if (!isset($this->__to_validate[$other_field])) throw new \Exception("Error with your validator. Field '$other_field' does not exist");
        if (empty($value) && empty($this->__to_validate[$other_field]))
            throw new ValidationIssue($message);

        return $value;
    }

    /**
     * Escape HTML and trim whitespace
     * 
     * @param string $value the value to sanitize
     * @return string sanitized user input
     */
    final function sanitize($value) {
        return trim(htmlspecialchars($value));
    }
}
