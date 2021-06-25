<?php

namespace Validation;

use \Validation\Exceptions\NoValue;

abstract class Normalize extends NormalizationHelpers {
    /** Accessable with any class which extends it */
    protected $__schema = [];
    protected $__dataset = [];
    protected $__index = [];
    protected $__normalize_out = true;

    // We set up our schema and store it
    function __construct($data = null, $normalize_get = true) {
        $this->__dataset = $data ?? [];
        $this->__normalize($normalize_get);

        $this->update_data($data);
    }

    /**
     * The keys of the return value are used as a schema for database values
     * 
     * Each key may have a `get` and `set` key. 
     * 
     * `set` values must be either:
     *    * An anonymous function
     *    * A string equal the name of a callable within $this context
     * 
     * NOTE: if no `set` is specified, a function named `set_[key_name]` will
     * be checked for and executed if found.
     * 
     * These functions recieve the following parameters:
     *    * $value   -> the unprocessed value
     *    * $context -> the current context ($this)
     *    * $name    -> the name of the current field
     * 
     * # Example schema entry:
     * ```php
     *   ['example_date' => [
     *       'get' => function ($value, $context) {
     *           return $value;
     *       },
     *       'set' => function ($value, $context, $name) {
     *           return $context->make_date($value);
     *       }
     *   ]];
     * ```
     * 
     * @return array returns an associative array
     */
    abstract function __get_schema(): array;

    public function __normalize($value) {
        $this->__normalize_out = $value;
    }

    /**
     * Get the raw data from the method
     * 
     * @return mixed raw dataset
     */
    public function __get_raw() {
        return $this->__dataset;
    }

    /**
     * Check if the schema contains $name and, if not, throw an exception.
     * 
     * Check if the dataset contains the value, if not throw an exception.
     * 
     * Check if the schema has a getter, provide the value of our dataset to the
     * getter and return its result.
     * 
     * Finally if no getter is found, return the value from the dataset
     * 
     */
    public function __get($name) { // Returns normalized user input
        $value = null;

        if (!key_exists($name, $this->__schema)) throw new NoValue("$name does not exist");

        // If the key doesn't exist, throw a custom NoValue exception.
        if (!key_exists($name, $this->__dataset)) throw new NoValue("$name does not exist in dataset");

        $value = $this->__dataset[$name]; // Get the value from the dataset

        // If we don't want normalizing, just return the value we already have
        if (!$this->__normalize_out) return $value;

        $method_name = "get_" . str_replace(".", "__", $name);
        if (key_exists('get', $this->__schema[$name])) $method_name = $this->__schema[$name]['get'];

        if (is_callable($method_name)) {
            // Run the value through the getter function
            $value = $method_name($value, $this, $name);
        } else if (method_exists($this, $method_name)) {
            $value = $this->{$method_name}($value, $this, $name);
        }

        return $value; // Return the value
    }

    /**
     * Check if $name is in schema, if not throw an error? Just ignore?
     * 
     * Check if setter exists in setter and run the value through the setter,
     * then save the return value to the dataset
     * 
     */
    public function __set($name, $value) { // Normalizes stored values

        if (!key_exists($name, $this->__schema)) return;

        // Check if a method named set_$name exists and execute it
        $method_name = "set_" . str_replace(".", "__", $name);
        if (key_exists('set', $this->__schema[$name])) $method_name = $this->__schema[$name]['set'];

        // Check if $method_name is either the name of a funciton or a function
        if (is_callable($method_name)) {
            // If the method is callable, call it and store its return value!
            $value = $method_name($value, $this, $name);
        } else if (method_exists($this, $method_name)) {
            // Check if method exists. This allows us to write a shared method
            // in the normalizer and use it between fields.
            $value = $this->{$method_name}($value, $this, $name);
        }

        // Update the value with what we've validated.
        $this->__dataset[$name] = $value;
        return $this->__dataset[$name];
    }


    public function __isset($name) {
        return key_exists($name, $this->__schema) && key_exists($name, $this->__dataset);
    }


    public function __unset($name) {
        unset($this->__dataset[$name]);
    }

    protected function update_data() {
        $this->__schema = $this->__get_schema();
        $this->__index = array_keys($this->__schema);
    }
}
