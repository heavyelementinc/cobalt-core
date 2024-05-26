<?php

namespace Cobalt\Maps\Traits;

use Cobalt\Maps\Exceptions\DirectiveException;
use Cobalt\Maps\GenericMap;
use Cobalt\Maps\Exceptions\SchemaExcludesUnregisteredKeys;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

trait Validatable {
    public array $__dataset = [];
    protected array $__issues = [];
    protected array $__schema = [];
    protected bool $__excludeUnregisteredKeys = __APP_SETTINGS__['Validation_exclude_unregistered_keys_by_default'];
    protected bool $__strictDataSubmissionPolicy = __APP_SETTINGS__['Validation_strict_data_submission_policy_by_default'];
    protected bool $__datasetValidated = false;
    protected $schema;

    abstract function __initialize_schema():void;
    abstract function __toResult(string $name, mixed $value, ?array $schema):SchemaResult|ObjectId;
    abstract function ingest(array $values):GenericMap;

    public $__validatedFields = [];

    public function __validate(array $toValidate) {
        if(!$this->__schema) $this->__initialize_schema();

        foreach($toValidate as $field => $value) {
            try {
                // The __validatedFields name NEEDS dot notation for nested elements
                // we want to update, so we use whatever fieldname is submitted as
                // the `name` attribute will include dot notation here.
                $this->__validate_field($field, $value);
            } catch (ValidationContinue $e) {
                continue;
            } catch (SchemaExcludesUnregisteredKeys $e) {
                if($this->__strictDataSubmissionPolicy) throw new BadRequest("Your request contained foreign keys", true);
                continue;
            }
        }
        if(count($this->__issues) !== 0) throw new ValidationFailed("Validation failed.", $this->__issues);

        $this->ingest($this->__validatedFields);
        $this->setValidationState(true);
        return $this;
    }

    /**
     * Use this in a filter, set, or other CRUD context to modify another field
     * @param string $name - The name of the field to update
     * @param mixed $value - The value of that field
     * @param bool $validateBeforeModification - [true] Validate before updating
     * @return void 
     * @throws SchemaExcludesUnregisteredKeys 
     * @throws DirectiveException 
     * @throws ValidationIssue
     */
    public function __modify(string $name, mixed $value, bool $validateBeforeModification = true):void {
        if($validateBeforeModification) $value = $this->__validate_field($name, $value);
        $this->__validatedFields[$name] = $value;
    }

    private function __validate_field($field, $value) {
        if(strpos($field, ".") !== false) {
            return $this->__validate_dot_notation($field, $value);
        }
        if($this->__excludeUnregisteredKeys) {
            if(!key_exists($field, $this->__schema)) throw new SchemaExcludesUnregisteredKeys('Schema excludes unregistered keys');
        }
        $result = $this->__toResult($field, $value, $this->__schema[$field]);
        try {
            if($value === null) {
                if($result->__isRequired()) throw new ValidationIssue("This field is required");
                throw new ValidationContinue("This field is empty and it's not required. Continuing.");
            }

            if(key_exists('filter', $this->__schema[$field])) {
                $value = $this->__schema[$field]['filter']($value);
            }

            $pattern = $result->getDirective("pattern");
            if($pattern) $this->testPattern($result, $value, $pattern);
            $validated = $result->filter($value);
            if(key_exists('set', $this->__schema[$field]) && is_callable($this->__schema[$field]['set'])) {
                $validated = $result->set($value);
            }
        } catch (ValidationContinue $e) {
            // If we catch a ValidationContinue, let's throw it again to
            // so we know to skip this field when we recieve this signal
            new ValidationContinue($e);
        } catch (ValidationIssue $e) { // Handle issues
            if (!isset($this->__issues[$field])) {
                $this->__issues[$field] = $e->getMessage();
                update("[name='$field']", ['message' => $e->getMessage(), 'invalid' => true]);
            }
            else {
                $this->__issues[$field] .= "\n" . $e->getMessage();
            }
        } catch (ValidationFailed $e) { // Handle subdoc failure
            $this->__issues[$field] = $e->data;
        }

        $this->__modify($field, $validated, false);
    }

    function __validate_dot_notation($field, $value) {
        $exploded = explode(".", $field);
        
        $mapFieldName = array_shift($exploded);
        // Hydrate our MapResult
        $map = $this->__toResult($mapFieldName, [], $this->__schema[$mapFieldName]);
        if($map instanceof MapResult) {
            // Explode our key
            $key = implode(".", $exploded);
            // Recursively run our validation pipeline
            $result = $map->getValue();
            if($result instanceof Validatable) {
                $result->__validate([$key => $value]);
            }
            // Run through `__validatedFields` and bring them into this context.
            foreach($result->__validatedFields as $fieldName => $validatedValue) {
                // If the key we submitted matches the fieldName, we want to update that
                // to the field we're trying to update.
                $fname = $fieldName;
                if($fname === $key) $fname = $field;
                $this->__modify($fname, $validatedValue, false);
            }
            return;
        };
        // if($map instanceof ArrayResult) return $map->getValue()
        throw new ValidationIssue("Updates with dot notation are only valid on `MapResult`s or `ArrayResult`s");
    }

    /**
     * Pass a validated PersistanceMap and this function will return an array
     * of operators for MongoDB
     * Any key that exists in the schema will use the $set operator unless
     * the field's schema entry has an `operator` key set.
     * 
     * The `operator` key must match a MongoDB Top Level Operator!
     * 
     * @param mixed $validated 
     * @return array 
     */
    function __operators($allowUnvalidated = false):array {
        $result = [];
        if(!$this->isValidated() && $allowUnvalidated === false) throw new ValidationFailed("Server configuration error");
        foreach($this->__validatedFields as $field => $value) {
            $v = $this->__hydrated[$field];
            $schema = $v->getSchema();
            $storable = $v->__getStorable();
            // if(!key_exists($field, $this->__schema)) {
            //     continue;
            // }
            
            if(!key_exists('operator', $schema)) {
                if(!key_exists('$set', $result)) $result['$set'] = [];
                $result['$set'][$field] = $storable;
                continue;
            }
            $operator = $schema['operator'];
            
            switch(gettype($operator)) {
                case "string":
                    if(!key_exists($operator, $result)) $result[$operator] = [];
                    $result[$operator][$field] = $storable;
                    break;
                case is_callable($operator):
                    $r = $operator($field, $storable);
                    $operator = key($r);
                    if(!key_exists($operator, $result)) $result[$operator] = [];
                    $result[$operator] = array_merge($result[$operator], $r[$operator]);
            }
        }

        return $result;
    }

    // function __operators_from_dot_notation($allowUnvalidated = false):array {
    //     $result = [];
    // }


    public function isValidated() {
        return $this->__datasetValidated;
    }

    public function setValidationState(bool $state) {
        $this->__datasetValidated = $state;
    }

    public function testPattern($result, $value, $pattern) {
        $flags = $result->getDirective("pattern_flags");
        $pattern = "/$pattern/$flags";
        if(preg_match($pattern, $value)) return;
        throw new ValidationIssue("Value does not match specified pattern");
    }
    
}