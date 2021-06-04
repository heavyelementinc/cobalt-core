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
    private $failed = [];

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
    abstract protected function __get_schema();

    /**
     * Returns a subset (possibly all) of $to_validate with their values having
     * been validated by the routine.
     * 
     * @param array $to_validate 
     * @return array validated subset of $to_validate 
     */
    final public function validate(array $to_validate, $using = null) {
        $this->__to_validate = $to_validate;

        $schema = $using;
        if ($using === null) $schema = $this->__get_schema();

        // Get a subset of allowed fieldnames from the submitted data
        $subset = $this->get_subset($schema);
        if (count($subset) <= 0) throw new BadRequest("No valid data submitted");

        $mutant = []; // Establish our mutant
        $problems = []; // Establish our problems container

        /** We have a $problems container so that we can run through our entire
         * list of items to be validated, find _all_ issues, then send those 
         * issues back to the client so they can be fixed.
         */

        foreach ($subset as $fieldname => $value) {
            if (key_exists("object_array", $schema[$fieldname])) {
                $mutant[$fieldname] = $this->handle_object_arrays($value, $schema[$fieldname]['object_array'], $fieldname);
                $this->__to_validate = $to_validate;
                continue;
            }

            // Check if methods are specified for this fieldname
            if (!key_exists("methods", $schema[$fieldname])) {
                if (!method_exists($this, $fieldname)) throw new \Exception("\"$fieldname\" does not have a validator method");
                $schema[$fieldname]['methods'] = [str_replace(".", "__", $fieldname)];
            }

            // Add the $value to the $mutant so we can update it through each
            // iteration in the list of methods.
            $mutant[$fieldname] = $value;

            // Loop through the available validation methods
            foreach ($schema[$fieldname]['methods'] as $index => $callable) {
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

        if ($using === null) $problems = array_merge($problems, $this->failed);

        /* The only reason we would have a count of $problems other than 0 is if
           there were ValidationProblems thrown. */
        if (count($problems) !== 0) throw new ValidationFailed("Validation failed.", $problems);
        /** Check if we have an __merge_private_fields method and, if so,
         * array_merge $mutant with the results of __merge_private_fields;
         */
        return array_merge($mutant, $this->__merge_private_fields($mutant));
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
    final protected function validate_email(string $value) {
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
    final protected function validate_phone($value, $min_length = 10) {
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
    final protected function required_field($value, $allow_false = true) {
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
    final protected function one_required($value, $other_field, $message = "One of these fields needs to be specified", $allow_false = true) {
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
    final protected function sanitize($value) {
        return trim(htmlspecialchars($value));
    }


    /* ============================== */
    /*       PRIVATE FUNCTIONS        */
    /* ============================== */

    /** After fields have been validated, the validate method will call __merge_private_fields
     * and its return value will be merged into the final validated array.
     * 
     * You can implement your own __merge_private_fields
     * 
     * @return array private fields to merged
     */
    private function __merge_private_fields($mutant): array {
        return []; // Must return an array
    }

    /**
     * Gets a subset of fields from the schema to be validated
     * @return array 
     * @throws Exception 
     */
    private function get_subset($schema) {
        // Get the schema from the abstract class and do type checking
        if (gettype($schema) !== "array") throw new \Exception("Invalid");

        // Create a subset of allowed fields from $this->__to_validate
        $subset = [];
        foreach ($schema as $key => $validate) {
            if (key_exists($key, $this->__to_validate)) $subset[$key] = $this->__to_validate[$key];
        }

        return $subset;
    }

    /**
     * Executes a method within $this context
     * 
     * @param string $callable a CALLABLE string or function
     * @param mixed $value the value to be validated
     * @param string $fieldname the fieldname of the current field
     * @param mixed $index the index into the 'methods' array
     * @return mixed a validated (and maybe mutated) version of $value
     */
    private function execute_method($callable, $value, $fieldname, $index) {
        // Covers methods in $this class and extensions
        if (method_exists($this, $callable)) return $this->{$callable}($value, $fieldname, $index);
        // Covers strings that match the name of a callable and anonymous functions
        if (is_callable($callable)) return $callable($value, $fieldname, $index);
    }

    private function handle_object_arrays($value, $schema, $field) {
        $mutant = [];
        foreach ($value as $index => $data) {
            try {
                $mutant[$index] = $this->validate($data, $schema);
            } catch (ValidationFailed $e) {
                $data = [];
                foreach ($e->data as $f => $d) {
                    $data[$field][$index][$f] = $d;
                }
                $this->failed = array_merge($this->failed, $data);
            }
        }

        return $mutant;
    }
}
