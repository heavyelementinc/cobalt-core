<?php
namespace Cobalt;

use Cobalt\SchemaPrototypes\SchemaResult;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

abstract class Validation {
    public array $__dataset = [];
    protected array $__issues = [];
    protected array $__schema;
    protected bool $__excludeUnregisteredKeys = __APP_SETTINGS__['Validation_exclude_unregistered_keys_by_default'];
    protected bool $__strictDataSubmissionPolicy = __APP_SETTINGS__['Validation_strict_data_submission_policy_by_default'];
    protected bool $__datasetValidated = false;
    protected $schema;

    abstract function __initialize_schema():void;
    abstract function __toResult(string $name, mixed $value, ?array $schema):SchemaResult|ObjectId;

    public $__validatedFields = [];

    public function validate(array $toValidate) {
        if(!$this->__schema) $this->__initialize_schema();

        foreach($toValidate as $field => $value) {
            try {
                $this->__validatedFields[$field] = $this->validate_field($field, $value);
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

    private function validate_field($field, $value) {
        if($this->__excludeUnregisteredKeys) {
            if(!key_exists($field, $this->__schema)) throw new SchemaExcludesUnregisteredKeys('Schema excludes unregistered keys');
        }
        $result = $this->__toResult($field, $value, $this->__schema[$field]);
        try {
            if($value === null) {
                if($result->__isRequired()) throw new ValidationIssue("This field is required");
                throw new ValidationContinue("This field is empty and it's not required. Continuing.");
            }
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
        return $validated;
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
    function operators($allowUnvalidated = false):array {
        $result = [];
        if(!$this->isValidated() && $allowUnvalidated === false) throw new ValidationFailed("Server configuration error");
        foreach($this->__dataset as $field => $value) {
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


    public function isValidated() {
        return $this->__datasetValidated;
    }

    public function setValidationState(bool $state) {
        $this->__datasetValidated = $state;
    }

    abstract function ingest(array $values):PersistanceMap;
}