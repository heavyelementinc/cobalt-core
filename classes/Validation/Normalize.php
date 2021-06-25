<?php

/**
 * Normalize.php - The Cobalt Normalization Class
 * 
 * This class provides a consistent means of sanitizing, validating, and/or
 * rejecting client input.
 * 
 * It also provides a way of normalizing input/output.
 *
 * # Write a `schema` class
 * We want to make sure ti extend \Validation\Normalize.
 * 
 * To see a working example, check \Validator\ExampleSchema.
 *  
 * # Use case 1: Returning data to client via API
 *
 * ```php
 *  function some_update_controller($id) {
 *      $normalize = new \SomeNamespace\ExtendsNormalize();
 *      $validated = $normalize->validate($_POST); // Returns normalized data
 *      $result = $this->updateOne(['_id' => $id], ['$set' => $validated]);
 *
 *      // Returns normalized data to client
 *      return iterator_to_array($normalize);
 *  }
 * ```
 * 
 * **or**
 * 
 * ```php
 *  function some_get_controller($id) {
 *      $instance = new \SomeNamespace\GetFromDb();
 *      $dbDocument = $instance->getById($id)
 *      $toClient = new \SomeNamespace\ExetndsNormalize($dbDocument);
 *      
 *      // Return a normalized version of the database document to client
 *      return iterator_to_array($toClient);
 *  }
 * ```
 * 
 * # Use case 2: Easy integration with templates
 * 
 * ```php
 *  function some_web_controller($id){
 *      $instance = new \SomeNamespace\GetFromDb();
 *      $dbDocument = $instance->getById($id)
 *      $toClient = new \SomeNamespace\ExetndsNormalize($dbDocument);
 * 
 *      add_vars([
 *          'title' => $toClient->document_title,
 *          'document' => $toClient
 *      ]);
 *      
 *      set_template("path/to/template.html");
 *  }
 * ```
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Validation;

use \Validation\Exceptions\NoValue;

abstract class Normalize extends NormalizationHelpers {
    protected $__schema = [];
    protected $__dataset = [];
    protected $__index = [];
    protected $__normalize_out = true;

    // We set up our schema and store it
    function __construct($data = null, $normalize_get = true) {
        $this->__dataset = $data ?? [];
        $this->__normalize($normalize_get);

        $this->init_schema($data);
    }

    /**
     * The keys of the return value are used as a schema for database values
     * 
     * Each key may have a `get` and `set` key. 
     * 
     * `get` or `set` values must be either:
     *    * An anonymous function
     *    * A string equal the name of a callable within $this context
     * 
     * NOTE: if no field is specified, a default value will be specified:
     *  * `get_[name]` or `set_[name]` respectively.
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
     * Get the raw data from this schema class
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

        // Step one: get the value from the __dataset so we can operate on it

        // Check if $name is in the dataset
        if (isset($this->__dataset[$name])) $value = $this->__dataset[$name]; // Get the value from the dataset
        else {
            // It's _not_ in the dataset, so let's try to get it with js lookups
            try {
                $value = lookup_js_notation($name, $this->__dataset);
            } catch (\Exception $e) {
                return;
            }
        }

        // If we don't want normalizing, just return the value we already have
        if (!$this->__normalize_out) return $value;

        $method_name = $this->__schema[$name]['get'] ?? "get_" . str_replace(".", "__", $name);

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
        if (isset($this->__schema[$name]['set'])) $method_name = $this->__schema[$name]['set'];

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

    /**
     * Magic function to determine if inaccessible properties exist.
     * 
     * @param mixed $name 
     * @return bool 
     */
    public function __isset($name) {
        if (isset($this->__dataset[$name])) return true;
        try {
            lookup_js_notation($name, $this->__dataset);
            return true;
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }


    /**
     * Magic function to unset inaccessible properties.
     * @param mixed $name 
     * @return void 
     */
    public function __unset($name) {
        unset($this->__dataset[$name]);
    }

    final protected function init_schema() {
        $this->__schema = $this->__get_schema();
        $this->initialize_schema();
        $this->__index = array_keys($this->__schema);
    }

    private function initialize_schema() {
        $schema = $this->__get_schema();
        foreach ($schema as $fieldname => $methods) {
            $this->find_method($fieldname, "get");
            $this->find_method($fieldname, "set");
        }
    }

    private function find_method($fieldname, $type) {
        if (isset($this->__schema[$fieldname][$type])) return;
        $method_name = "$type" . "_" . str_replace(".", "__", $fieldname);
        if (method_exists($this, $method_name)) $this->__schema[$fieldname][$type] = $method_name;
    }
}
