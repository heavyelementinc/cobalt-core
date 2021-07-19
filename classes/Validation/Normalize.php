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
use \Validation\Exceptions\ValidationIssue;
use \Validation\Exceptions\ValidationFailed;

abstract class Normalize extends NormalizationHelpers {
    protected $__schema = [];
    protected $__dataset = [];
    protected $__index = [];
    protected $__to_validate = [];
    protected $__normalize_out = true;
    protected $__prototypes = [
        'raw', // Returns the un-normalized value for the field
        'valid', // 
        'options',
        'restore',
        'json'
        // 'display',
    ];

    // We set up our schema and store it
    function __construct($data = null, $normalize_get = true) {
        $this->__dataset = $data ?? [];
        $this->__normalize($normalize_get);

        $this->init_schema();
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


    /** Validation routine
     * 
     * @param array $data the data to be validated
     * @return array Validated data
     * @throws ValidationFailed 
     */
    public function __validate($data) {
        $this->__to_validate = $data;
        $schema = $this->get_schema_subset(array_keys($data));
        $issues = [];
        foreach ($schema as $name => $value) {
            if (!isset($data[$name])) continue;
            try {
                // Run the setter function by assigning value which can throw issues
                $this->{$name} = $data[$name];
            } catch (ValidationIssue $e) { // Handle issues
                if (!isset($issues[$name])) $issues[$name] = $e->getMessage();
                else $issues[$name] .= "\n" . $e->getMessage();
            } catch (ValidationFailed $e) { // Handle subdoc failure
                $issues[$name] = $e->data;
            }
        }

        if (count($issues) !== 0) throw new ValidationFailed("Validation failed.", $issues);

        return array_merge($this->__dataset, $this->__merge_private_fields($this->__dataset));
    }

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
    public function __get($n) { // Returns normalized user input
        $name = $n;
        $value = null;
        $proto = $this->__get_prototype($name); // $n = "name.raw"; $proto = ['name','raw']
        if ($proto !== false) $name = $proto[0];

        // Step one: get the value from the __dataset so we can operate on it

        // Check if $name is in the dataset
        if (isset($this->__dataset[$name])) $value = $this->__dataset[$name]; // Get the value from the dataset
        else {
            // It's _not_ in the dataset, so let's try to get it with js lookups
            try {
                // Example might be $n = "contacts.0.name"
                $value = lookup_js_notation($name, $this->__dataset);
            } catch (\Exception $e) {
                return;
            }
        }

        if ($n !== $name && $proto !== false) return $this->__execute_prototype($value, $name, $proto[1]);

        // If we don't want normalizing, just return the value we already have
        if (!$this->__normalize_out) return $value;

        $method_name = $this->__schema[$name]['get'] ?? "get_" . str_replace(".", "__", $name);

        if (is_callable($method_name)) {
            // Run the value through the getter function
            $value = $method_name($value, $name);
        } else if (method_exists($this, $method_name)) {
            $value = $this->{$method_name}($value, $name);
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
        if (key_exists('each', $this->__schema[$name])) {
            $this->__dataset[$name] = $this->subdocument($value, $this->__schema[$name]['each']);
            return $this->__dataset[$name];
        }
        // Ensure we want to save our $value to the dataset (in other words, if
        // set === false, ignore this field)
        if (isset($this->__schema[$name]['set']) && $this->__schema[$name]['set'] === false) return;

        // Check if a method named set_$name exists and execute it
        $method_name = "set_" . str_replace(".", "__", $name);
        if (isset($this->__schema[$name]['set'])) $method_name = $this->__schema[$name]['set'];

        // Check if $method_name is either the name of a funciton or a function
        if (is_callable($method_name)) {
            // If the method is callable, call it and store its return value!
            $value = $method_name($value, $name);
        } else if (method_exists($this, $method_name)) {
            // Check if method exists. This allows us to write a shared method
            // in the normalizer and use it between fields.
            $value = $this->{$method_name}($value, $name);
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
    public function __isset($n) {
        $name = $n;
        $proto = $this->__get_prototype($name);
        if ($proto !== false) {
            $name = $proto[0];
            if (isset($this->__schema[$name]) && in_array($proto[1], $this->__prototypes)) return true;
        }

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




    protected function subdocument($value, $schema) {
        $doc = new Subdocument($value, $schema, $context);
        return $doc->__validate($value);
    }



    final protected function init_schema($schema = []) {
        $this->__schema = array_merge($this->__get_schema(), (array)$schema);
        $this->initialize_schema();
        $this->__index = array_keys($this->__schema);
    }

    final private function initialize_schema() {
        $schema = $this->__get_schema();
        foreach ($schema as $fieldname => $methods) {
            $this->find_method($fieldname, "get");
            $this->find_method($fieldname, "set");
        }
    }



    final private function get_schema_subset($keys) {
        $result = [];
        foreach ($this->__schema as $key => $val) {
            if (in_array($key, $keys)) $result[$key] = $val;
        }
        return $result;
    }


    final private function find_method($fieldname, $type) {
        if (isset($this->__schema[$fieldname][$type])) return;
        $method_name = "$type" . "_" . str_replace(".", "__", $fieldname);
        if (method_exists($this, $method_name)) $this->__schema[$fieldname][$type] = $method_name;
    }




    final private function __get_prototype($name) {
        // $name = "value.options"
        // Get the last instance of a "."
        $pos = strripos($name, ".");
        // If $pos is false we know there's no prototype
        if ($pos === false) return false;
        $proto = substr($name, $pos += 1); // $proto = "options"
        if (!in_array($proto, $this->__prototypes)) return false;
        return [substr($name, 0, $pos - 1), $proto]; // ['value', 'options']
    }


    final private function __execute_prototype($value, $fieldname, $prototype) {
        $method_name = "__proto_$prototype";
        if (method_exists($this, $method_name)) return $this->{$method_name}($value, $fieldname);
        return "";
        // if (isset($this->__schema[$fieldname][$prototype])) {
        //     return $this->__schema[$fieldname][$prototype]($value, $fieldname);
        // }
    }

    /** Returns the raw value rather than the `get`ted value, useful when 
     * handling markdown if the `get` result is parsed as HTML.
     */
    final private function __proto_raw($val, $field) {
        if (isset($this->__dataset[$field])) {
            return $this->__dataset[$field];
        }
        return '';
    }

    /** Allows us to specify in the schema an alternate display method */
    final private function __proto_display($val, $field) {
        if (isset($this->__schema[$field]['display'])) {
            return $this->__schema[$field]['display']($val, $field);
        }
        return '';
    }

    /** Executes the 'valid' method defined in the schema and returns results */
    final private function __proto_valid($val, $field) {
        if (isset($this->__schema[$field]['valid'])) {
            if (is_callable($this->__schema[$field]['valid'])) return $this->__schema[$field]['valid']($val, $this, $field);
            return $this->__schema[$field]['valid'];
        }
        return [];
    }

    /** Returns a list of HTML options */
    final private function __proto_options($val, $field) {
        $valid = $this->__proto_valid($val, $field);
        $options = "";
        foreach ($valid as $k => $v) {
            $value = $v;
            $data = "";
            if (gettype($v) === "array") {
                $value = $v['value'];
                unset($v['value']);
                foreach ($v as $attr => $value) {
                    $data .= " data-$attr=\"$value\"";
                }
            }
            $selected = ($val === $k) ? "selected='selected'" : "";
            $options .= "<option value='$k'$data $selected>$v</option>";
        }
        return $options;
    }

    /** Unused prototype?? */
    final private function __proto_restore($val, $field) {
        return "";
    }

    final private function __proto_json($val, $field) {
        return json_encode($val);
    }
}
