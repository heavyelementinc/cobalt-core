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
 * Words of warning
 * > If a subdocument also has a `get`ter defined, then the get method will be
 * > executed when calling that field.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Validation;

use ArrayAccess;
use Countable;
use Exception;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\UTCDateTime;
use TypeError;
use \Validation\Exceptions\NoValue;
use \Validation\Exceptions\ValidationIssue;
use \Validation\Exceptions\ValidationFailed;

abstract class Normalize extends NormalizationHelpers implements JsonSerializable, Iterator, ArrayAccess, Countable {
    protected $__schema = [];
    public $__dataset = [];
    protected $__index = [];
    protected $__to_validate = [];
    protected $__normalize_out = true;
    protected $__isEmptyDoc = true;
    protected $__prototypes = [
        'raw',        // Returns the un-normalized value for the field
        'valid',      // Returns the valid options for selectable data
        'options',    // Returns an HTML output of OPTIONS
        'restore',    // Unknown
        'json',       // Converts the data to JSON
        'json_pretty',// Pretty prints JSON
        'display',    // Display lets us pretty-fy output in our schema
        'class',      // A definable prototype function that is meant for use in attributes
        'attrs',      // Returns HTML attributes
        'length',     // The length of a string, the number of elements in an array
        'md',         // Parses markdown into HTML
        'capitalize', // Capitalizes the first letter of a string
        'uppercase',  // Upper cases the entire string
        'lowercase',  // Lower cases the entire string
        'embed',      // Convert value into an HTML tag based on metadata or file extension
        'last',       // Select the last element of an array
        'strip',      // Converts text to markdown and then strips the tags. Should give plain text.
        'gmt',
        'immutable',
    ];

    protected $__global_fields = [];

    protected function getGlobalField($name) {
        if(!key_exists($name, $this->__global_fields)) return null;
        return $this->__global_fields[$name]['callback']();
    }

    // We set up our schema and store it
    function __construct($data = null, $normalize_get = true) {
        $this->__dataset = $data ?? [];
        
        if(empty($data) || is_null($data)) {
            $this->__isEmptyDoc = true;
        } else {
            $this->__isEmptyDoc = false;
        }
        
        $this->__normalize($normalize_get);

        $this->__global_fields = [
            'isEmptyDoc' => [
                'callback' => fn () => $this->__isEmptyDoc
            ],
            'newDocDisabled' => [
                'callback' => fn () => ($this->__isEmptyDoc) ? " disabled=disabled" : "",   // Check if there are any files
            ],
            'newDocSubmit' => [
                'callback' => fn () => ($this->__isEmptyDoc) ? "<button type=submit>Submit</button>" : "",
            ],
            'newDocAutosave' => [
                'callback' => fn () => ($this->__isEmptyDoc) ? "" : " autosave=autosave",
            ],
            'newDocAutosaveFieldset' => [
                'callback' => fn () => ($this->__isEmptyDoc) ? "" : " autosave=fieldset",
            ],
            'newDocMethod' => [
                'callback' => fn () => ($this->__isEmptyDoc) ? "POST" : "PUT"
            ]
        ];
        
        $this->init_schema();

        $this->__dataset = array_merge($this->default_values(), doc_to_array($this->__dataset));

        $this->__normalize_data();
        // Only enable pronoun prototypes if the 'pronoun_set' key is in the schema.
        // Do we actually want this?
        if (key_exists('pronoun_set', $this->__schema)) {
            $this->__init_pronoun_set();
        }
    }

    function default_values():array {
        return []; //array_fill_keys(array_keys($this->__schema),null);
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
     * The serialize entry directs the iterable
     * 
     * # Example schema entry:
     * ```php
     *   ['example_date' => [
     *       'get' => function ($value, $context) {
     *           return $value;
     *       },
     *       'set' => function ($value, $context, $name) {
     *           return $context->make_date($value);
     *       },
     *       'serialize' => 'display' // string<name of prototype>|true
     *   ]];
     * ```
     * 
     * @return array returns an associative array
     */
    abstract function __get_schema(): array;

    function __edit() {
        if(!$this->edit_view) return "Cannot display this document. Please set an edit view using \$this-&gt;edit_view in " .  $this->__CLASS__ . " or override the __edit method.";
        return view($this->edit_view, ['doc' => $this]);
    }

    function __index() {
        if(!$this->index_view) $this->index_view = "/CRUD/admin/default-list-item.html";
        return view($this->index_view, ['doc' => $this]);
    }

    function __public() {
        if(!$this->public_view) return "Cannot display this document. Please set an edit view using \$this-&gt;edit_view in " .  $this->__CLASS__ . " or override the __edit method.";
        return view($this->public_view, ['doc' => $this]);
    }

    function __list() {
        if(!$this->list_view) $this->list_view = "/CRUD/web/default-list-item.html";
        return view($this->list_view, ['doc' => $this]);
    }

    /**
     * Pass validated values and this will return an array of operators
     * Any key that exists in the schema will use the $set operator unless
     * the field's schema entry has an `operator` key set.
     * 
     * The `operator` key must match a MongoDB Top Level Operator!
     * 
     * @param mixed $validated 
     * @return array 
     */
    function __operators($validated) {
        $result = [];
        foreach($validated as $field => $value) {
            if(!key_exists($field, $this->__schema)) continue;
            if(!key_exists('operator', $this->__schema[$field])) {
                if(!key_exists('$set', $result)) $result['$set'] = [];
                $result['$set'][$field] = $value;
                continue;
            }
            $operator = $this->__schema[$field]['operator'];
            
            switch(gettype($operator)) {
                case "string":
                    if(!key_exists($operator, $result)) $result[$operator] = [];
                    $result[$operator][$field] = $value;
                    break;
                case is_callable($operator):
                    $r = $operator($field, $value);
                    $operator = key($r);
                    if(!key_exists($operator, $result)) $result[$operator] = [];
                    $result[$operator] = array_merge($result[$operator], $r[$operator]);
            }
        }
        
        return $result;
    }

    /**
     * Validation routine.
     * 
     * @alias __validate
     * @param mixed $data 
     * @param mixed $createSubset 
     * @return array 
     * @throws ValidationFailed 
     */
    public function validate($data, $createSubset = true) {
        return $this->__validate($data,$createSubset);
    }

    public $issues = [];

    /** Validation routine
     * 
     * @param array $data the data to be validated
     * @param bool $createSubset allow 
     * @return array Validated data
     * @throws ValidationFailed 
     */
    public function __validate($data, $createSubset = true) {
        $this->__to_validate = &$data;
        if ($createSubset) $schema = $this->get_schema_subset(array_keys($data));
        else $schema = $this->init_schema();
        $this->issues = [];
        foreach ($schema as $name => $value) {
            if (!isset($data[$name]) && $createSubset === true) continue;
            try {
                // Run the setter function by assigning value which can throw issues
                $this->{$name} = (isset($this->__to_validate[$name])) ? $this->__to_validate[$name] : null;
            } catch (ValidationIssue $e) { // Handle issues
                if (!isset($this->issues[$name])) {
                    $this->issues[$name] = $e->getMessage();
                    update("[name='$name']", ['message' => $e->getMessage(), 'invalid' => true]);
                }
                else {
                    $this->issues[$name] .= "\n" . $e->getMessage();
                }
            } catch (ValidationFailed $e) { // Handle subdoc failure
                $this->issues[$name] = $e->data;
            }
        }

        if (count($this->issues) !== 0) throw new ValidationFailed("Validation failed.", $this->issues);

        return merge($this->__dataset, $this->__merge_private_fields($this->__dataset));
    }

    public function __normalize($value) {
        $this->__normalize_out = $value;
    }

    public function __normalize_data() {
        foreach($this->__schema as $field => $methods) {
            if(key_exists('each', $methods)) $this->__dataset[$field] = $this->subdocument($this->__dataset[$field], $methods['each']);
        }
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
     * Map external data to a schema. This is useful if you have API data and want
     * to normalize it into database entries.
     * 
     * In your schema, specify a "map" key for each of the items you wish to have
     * data mapped to. For example:
     * 
     * "note" => [
     *     "map" => "some.external.note"
     * ],
     * "email" => [
     *     "map" => function ($data, $toDataset, $key, $schemaData) {
     *          return $data->user->auth->email;
     *     }
     * ]
     * 
     * Map may be either a string or a function. Anything else will throw a
     * TypeError.
     * 
     * Any callable will be passed the parameters of the __map() invocation as 
     * well as the key and data of the schema entry in that order.
     * 
     * Provide the data you want to map as the first argument, 
     * 
     * @param mixed $data 
     * @param bool $toDataset 
     * @return array 
     * @throws Exception 
     * @throws TypeError 
     */
    public function __map(mixed $data, bool $toDataset = false) {
        $processed = [];
        // Loop through the schema
        foreach($this->__schema as $key => $schemaData) {
            // Look for any schema items that have a "map" key
            if(!in_array("map", $schemaData)) continue;
            switch(gettype($schemaData['map'])) {
                case "callable":
                    // Carry out the mapping process for callable items
                    $processed[$key] = $this->set_map($key, $schemaData['map']($data, $toDataset, $key, $schemaData));
                    break;
                case "string":
                    // Look up the value if provided a string (will throw an exception upon failure)
                    try {
                        $processed[$key] = $this->set_map($key, lookup_js_notation($schemaData['map'], $data, true));
                    } catch(Exception $e) {
                        $processed[$key] = null;
                    }
                    break;
                default:
                    // Throw a TypeError if $d['map'] is not a string or callable
                    throw new TypeError("An unexpected map value was set for `$key` in " . $this::class);
            }
        }
        // If $toDataset is true, overwrite the values we just updated
        if($toDataset) $this->__dataset = array_merge($this->__dataset, $processed);
        // Return the items we just updated
        return $processed;
    }

    private function set_map($key, $value) {
        if(key_exists('set', $this->__schama) && is_callable($this->__schema['set'])) {
            return $this->__schema['set']($value);
        }
        return $value;
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
        $global = $this->getGlobalField($name);
        if($global !== null) return $global;

        $value = null;
        $proto = $this->__get_prototype($name); // $n = "name.raw"; $proto = ['name','raw']
        if ($proto !== false) $name = $proto[0];

        // Step one: get the value from the __dataset so we can operate on it

        $getOperator = false;
        $pos = strpos($name,"?");
        if($pos !== false) {
            $getOperator = substr($name, $pos + 1);
            $name = substr($name, 0, $pos);
        }

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
        if($pos !== false) $value = lookup_js_notation($getOperator, $this->__callable($value, $name));
        if ($n !== $name && $proto !== false) return $this->__execute_prototype($value, $name, $proto[1]);

        // If we don't want normalizing, just return the value we already have
        // WTF is this doing?!?
        if ($this->__normalize_out === false) return $value;

        // if (isset($this->__schema[$name]['each']) && !isset($this->__schema[$name]['get'])) {
        //     $subdoc = new Subdocument($value, $this->__schema[$name]['each'], $this);
        //     return iterator_to_array($subdoc);
        // }

        if($pos) return $value;
        return $this->__callable($value, $name); // Return the value
    }

    private function __callable($value, $name) {
        $method_name = $this->__schema[$name]['get'] ?? "get_" . str_replace(".", "__", $name);

        if (is_callable($method_name)) {
            // Run the value through the getter function
            $value = $method_name($value, $name, $this);
        } else if (method_exists($this, $method_name)) {
            $value = $this->{$method_name}($value, $name, $this);
        }
        return $value;
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

        if(key_exists("max_char_length",$this->__schema[$name]) && strlen($value) > $this->__schema[$name]['max_char_length']) {
            throw new ValidationIssue("Maximum character length exceeded.");
        }

        $set = $this->__schema[$name]['set'];

        // Ensure we want to save our $value to the dataset (in other words, if
        // set === false, ignore this field)
        if (isset($set) && in_array($set, [null, false])) return;

        // Check if a method named set_$name exists and execute it
        $method_name = "set_" . str_replace(".", "__", $name);
        if (isset($set)) $method_name = $set;

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
        $doc = new Subdocument($value, $schema, $this);
        // $subdoc = [];
        // foreach($doc->__schema as $field => $f) {
        //     $subdoc[$field] = $doc->{$field};
        // }
        // return $subdoc;
        return $doc;//$doc->__validate($value);
    }

    protected function each($schema, $data) {
        $processed = [];
        foreach($data as $doc) {
            array_push($processed, new $schema($doc));
        }
        return $processed;
    }

    final protected function init_schema($schema = []) {
        // We write this schema to __schema
        $this->__schema = array_merge($this->__get_schema(), (array)$schema);
        $this->initialize_schema();
        $this->__index = array_keys($this->__schema);
        return $this->__schema;
    }

    private function initialize_schema() {
        foreach ($this->__schema as $fieldname => $directives) {
            $this->find_method($fieldname, "get");
            $this->find_method($fieldname, "set");
            if(key_exists("default", $directives)) $this->__dataset[$fieldname] = $directives['default'];
            // if(key_exists('serialize', $methods)) {
            //     array_push($this->__index);
            // }
        }
    }



    private function get_schema_subset($keys) {
        $result = [];
        foreach ($this->__schema as $key => $val) {
            if (in_array($key, $keys)) $result[$key] = $val;
        }
        return $result;
    }


    private function find_method($fieldname, $type) {
        if (isset($this->__schema[$fieldname][$type])) return;
        $method_name = "$type" . "_" . str_replace(".", "__", $fieldname);
        if (method_exists($this, $method_name)) $this->__schema[$fieldname][$type] = $method_name;
    }




    private function __get_prototype($name) {
        // $name = "value.options"
        // Get the last instance of a "."
        $pos = strripos($name, ".");
        // If $pos is false we know there's no prototype
        if ($pos === false) return false;
        $proto = substr($name, $pos += 1); // $proto = "options"
        $field = substr($name, 0, $pos - 1);
        if (in_array($proto, $this->__prototypes)) return [$field, $proto]; // ['value', 'options']
        if (key_exists($field, $this->__schema) && key_exists($proto, $this->__schema[$field])) return [$field, $proto];
        return false;
    }


    private function __execute_prototype($value, $fieldname, $prototype) {
        $method_name = "__proto_$prototype";
        $got = $value;
        try{ 
            if(isset($this->__schema[$fieldname]['get'])) $got = $this->__schema[$fieldname]['get']($value, $fieldname, $this) ?? $value;
        } catch (\Error $e) {
            
        }
        if (method_exists($this, $method_name)) return $this->{$method_name}($got, $fieldname, $value);
        if (key_exists($fieldname, $this->__schema) && key_exists($prototype, $this->__schema[$fieldname])) return $this->__schema[$fieldname][$prototype]($got, $fieldname, $value);
        return "";
        // if (isset($this->__schema[$fieldname][$prototype])) {
        //     return $this->__schema[$fieldname][$prototype]($value, $fieldname);
        // }
    }

    /** Returns the raw value rather than the `get`ted value, useful when 
     * handling markdown if the `get` result is parsed as HTML.
     */
    private function __proto_raw($val, $field) {
        if (isset($this->__dataset[$field])) {
            return $this->__dataset[$field];
        }
        return '';
    }

    private function __proto_strip($val, $field) {
        return markdown_to_plaintext($val);
    }

    private function __proto_gmt($val, $field) {
        if($val instanceof UTCDateTime) $val = $val->toDateTime()->getTimestamp();
        return date('r', $val);
    }

    private function __proto_immutable($val, $field) {
        if(in_array($field, $this->__dataset) && $this->__dataset[$field]) return " readonly=\"readonly\"";
        return "";
    }

    /** Allows us to specify in the schema an alternate display method
     *  If no 'display' proto is specified, this will automatically look for a
     *  valid proto and, if found, will look up the name of $val
     * 
     *  If $val is not in 'valid', $val will be returned
     */
    private function __proto_display($val, $field) {
        if (isset($this->__schema[$field]['display'])) {
            $fn = $this->__schema[$field]['display'];
            if(is_callable($fn)) return $fn($val, $field, $this);
            return $fn;
        } else if (isset($this->__schema[$field]['valid'])) {
            $valid = $this->__schema[$field]['valid'];
            if (is_callable($valid)) $valid = $valid($val, $field, $this);
            $type = gettype($val);
            if ($type === "object" || $type === "array") return $this->__proto_display_array_items($val, $valid, $this);
            if (key_exists($val, $valid)) return $valid[$val];
        }
        if($val instanceof \MongoDB\BSON\UTCDateTime) return $this->get_date($val, 'verbose');
        return $val;
    }

    private function __proto_class($val, $field) {
        if(!isset($this->__schema[$field]['class'])) return "";
        return $this->__schema[$field]['class']($this->{$field}, $field);
    }
    
    private function __proto_display_array_items($values, $valid) {
        $labeled = [];
        foreach($values as $val) {
            if(key_exists($val, $valid)) array_push($labeled, $valid[$val]);
            else array_push($labeled, $val);
        }
        return implode(", ", $labeled);
    }

    /** Executes the 'valid' method defined in the schema and returns results */
    private function __proto_valid($val, $field) {
        if ($field === "pronoun_set") return $this->valid_pronouns();
        if (isset($this->__schema[$field]['valid'])) {
            if (is_callable($this->__schema[$field]['valid'])) return $this->__schema[$field]['valid']($val, $field);
            return $this->__schema[$field]['valid'];
        }
        return [];
    }

    private function __proto_attrs($val, $field) {
        if(!isset($this->__schema[$field]['attrs'])) return $val;
        return $this->__schema[$field]['attrs']($val);
    }

    /** Returns a list of HTML options */
    private function __proto_options($val, $field) {
        $valid = $this->__proto_valid($val, $field);
        $gotten_value = $this->{$field};
        if($gotten_value instanceof \MongoDB\Model\BSONArray) $gotten_value = $gotten_value->getArrayCopy();

        $type = gettype($val);

        switch($type) {
            case $val instanceof \MongoDB\Model\BSONArray:
                $val = $val->getArrayCopy();
            case "array":
                $v = [];
                foreach($val as $o) {
                    $v[(string)$o] = $o;
                }
                $valid = array_merge($v ?? [], $valid ?? []);
                $type = gettype($val);
        }

        $options = "";
        foreach ($valid as $k => $v) {
            $value = $v;
            $data = "";
            if (gettype($v) === "array") {
                $v = $v['value'];
                unset($value['value']);
                foreach ($value as $attr => $val) {
                    $data .= " data-$attr=\"$val\"";
                }
            }
            $selected = "";
            switch ($type) {
                case "string":
                    $selected = ($val == $k || $gotten_value == $k) ? "selected='selected'" : "";
                    break;
                case "object":
                    if ($val instanceof \MongoDB\Model\BSONArray) {
                        $selected = (in_array($k, $val->getArrayCopy())) ? "selected='selected'" : "";
                    } elseif($val instanceof \MongoDB\BSON\ObjectId && (string)$val === $k) {
                        $selected = "selected='selected'";
                    }
                    break;
                case "array":
                    $selected = (in_array($k, $val) || in_array($k, $gotten_value)) ? "selected='selected'" : "";
                    break;
            }
            $options .= "<option value='$k'$data $selected>$v</option>";
        }
        return $options;
    }

    /** Unused prototype?? */
    private function __proto_restore($val, $field) {
        return "";
    }

    private function __proto_json($val, $field) {
        return json_encode($val);
    }

    private function __proto_json_pretty($val, $field) {
        return json_encode($val, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    private function __proto_md($val, $field) {
        $val = $this->{$field};
        if (!$val) return "";
        return from_markdown($val);
    }

    private function __proto_length($val, $field) {
        if(gettype($val) == "string") return strlen($val);

        return (is_countable($val)) ? count($val) : null;
    }

    private function __proto_capitalize($val, $field) {
        return ucfirst($val);
    }

    private function __proto_uppercase($val, $field) {
        return strtoupper($val);
    }

    private function __proto_lowercase($val, $field) {
        return strtolower($val);
    }

    private function __proto_last($val, $field) {
        if(!is_array($val)) return $val;
        return $val[count($val) - 1];
    }

    private function __proto_embed($val, $field) {
        if(isset($this->__dataset['meta'])) {
            return $this->embed_from_meta($val, $field);
        }
        return $this->embed_from_value($val, $field);
    }

    private function embed_from_meta($val, $field) {
        $type = $this->__dataset['type'];
        // if(!$type) return $this->embed_from_value($val, $field);
        $mimetype = $this->__dataset['meta']['meta']['mimetype'] ?? $this->__dataset['meta']['mimetype'];
        $pos = explode("/",$mimetype);
        $sub = $pos[0];
        $enc = $pos[1];
        $rt = $this->{'value'};
        if(is_array($rt)) {
            $rt = $rt[count($rt) - 1];
        }
        $w = $this->__dataset['meta']['display_width'] ?? $this->__dataset['meta']['width'] ?? $this->__dataset['meta']['meta']['width'];
        $h = $this->__dataset['meta']['display_height'] ?? $this->__dataset['meta']['height'] ?? $this->__dataset['meta']['meta']['height'];
        switch(strtolower($type)) {
            case "image":
                $rt = "<img src='$rt' width=\"$w\" height=\"$h\">";
                break;
            case "video":
                $rt = "<video width=\"$w\" height=\"$h\" ".$this->{'meta.controls.display'}.$this->{'meta.loop.display'}.$this->{'meta.autoplay.display'}.$this->{'meta.mute.display'}."><source src='$rt' type='$mimetype'></video>";
                break;
            case "audio":
                $rt = "<audio ".$this->{'meta.mute.display'}.$this->{'meta.loop.display'}.$this->{'meta.controls.display'}."><source src='$rt' type='$mimetype'></audio>";
                break;
            case "href":
                $fs = $this->{'meta.allowfullscreen'};
                $allow = $this->{'meta.allow'};
                $title = $this->{'meta.title'};
                $rt = "<iframe src=\"$rt\" name=\"$enc\" scrolling=\"no\" frameborder=\"0\" width=\"$w\" height=\"$h\" $fs $allow $title></iframe>";
                break;
        }

        return $rt;
    }

    private function embed_from_value($val, $field) {
        return $val;
    }

    function get_pronoun_table() {
        $index = null;
        if (isset($this->__dataset['pronoun_set'])) $index = $this->__dataset['pronoun_set'];
        if (is_null($index)) $index = "0";
        if (key_exists($index, $this->pronouns_table)) return $this->pronouns_table[$index];
        throw new \Exception("The specified pronoun index is missing.");
    }

    function __proto_they() {
        return $this->get_pronoun_table()["they"];
    }

    function __proto_them() {
        return $this->get_pronoun_table()["them"];
    }

    function __proto_their() {
        return $this->get_pronoun_table()["their"];
    }

    function __proto_theirs() {
        return $this->get_pronoun_table()["theirs"];
    }

    function __proto_themselves() {
        return $this->get_pronoun_table()["themselves"];
    }

    function __proto_are() {
        return $this->get_pronoun_table()["are"];
    }

    public function jsonSerialize():mixed {
        if (!$this->__dataset) return []; // throw new \Exception("This normalizer has not been supplied with iterable data");

        $mutant = [];
        foreach ($this->__dataset as $name => $value) {
            // if($value instanceof Subdocument) {
            //     $mutant[$name] = $value->__all_fields();
            //     continue;
            // }
            $mutant[$name] = $this->__get($name);
        }
        foreach($this->__schema as $name => $data) {
            if(!key_exists('serialize', $data)) continue;
            switch($data['serialize']) {
                case true:
                    $mutant[$name] = $this->__get($name);
                    break;
                case in_array($name, $this->__prototypes):
                    $mutant[$name] = $this->{"$name.$data[serialize]"};
                    break;
            }
        }

        return $mutant;
    }

    public function __init_pronoun_set(): void {
        $pronoun_types = [
            'they' => [
                'get' => fn () => $this->__proto_they(),
            ],
            'them' => [
                'get' => fn () => $this->__proto_them(),
            ],
            'their' => [
                'get' => fn () => $this->__proto_their(),
            ],
            'theirs' => [
                'get' => fn () => $this->__proto_theirs(),
            ],
            'themselves' => [
                'get' => fn () => $this->__proto_themselves(),
            ],
            'are' => [
                'get' => fn () => $this->__proto_are(),
            ],
        ];
        $this->__prototypes = array_merge($this->__prototypes, array_keys($pronoun_types));
        $this->add_to_schema($pronoun_types);

        // foreach($pronoun_types as $type => $data) {
        //     if(!isset($this->__dataset[$type])) $this->__dataset[$type] = $this->{"__proto_$type"}();
        // }
    }

    /**
     * Does not override existing schema items
     */
    public function add_to_schema($to_add) {
        $this->__schema = array_merge($to_add, $this->__schema);
        $this->__index  = array_keys($this->__schema);
    }


    /* =================
        ITERATOR METHODS
       ================= */
    private $__position = 0;

    public function rewind(): void {
        $this->__position = 0;
    }

    public function current():mixed {
        return $this->{$this->__index[$this->__position]};
    }

    public function key() {
        return $this->__index[$this->__position];
    }

    public function next(): void {
        ++$this->__position;
    }

    public function valid(): bool {
        if (!isset($this->__index[$this->__position])) return false;
        return isset($this->__dataset[$this->__index[$this->__position]]);
    }

    /** ================
     *    ARRAY ACCESS
     *  ================
     */

    public function offsetExists($offset): bool {
        return $this->__isset($offset);
    }

    public function offsetGet($offset):mixed {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value): void {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset): void {
        unset($this->__dataset[$offset]);
    }

    public function __all_fields() {
        $mutant = [];

        foreach ($this->__schema as $field => $value) {
            if (is_array($value) && key_exists('set', $value) && $value['set'] === null) continue;
            $mutant[$field] = $this->{$field};
        }

        return $mutant;
    }

    /** COUNTABLE */

    public function count():int {
        return count($this->__dataset);
    }
}
