<?php
namespace Cobalt;

use Cobalt\SchemaPrototypes\SchemaResult;
use Exceptions\HTTP\BadRequest;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

abstract class Validation {
    protected array $__issues;
    protected array $__schema;
    protected bool $__excludeUnregisteredKeys = __APP_SETTINGS__['Validation_exclude_unregistered_keys_by_default'];
    protected bool $__strictDataSubmissionPolicy = __APP_SETTINGS__['Validation_strict_data_submission_policy_by_default'];

    abstract function __initialize_schema():void;
    abstract function datatype_persistance($name, $value):SchemaResult;

    public function validate(array $toValidate) {
        if(!$this->__schema) $this->__initialize_schema();

        $mutant = [];
        foreach($toValidate as $field => $value) {
            try {
                $mutant[$field] = $this->validate_field($field, $value);
            } catch (SchemaExcludesUnregisteredKeys $e) {
                if($this->__strictDataSubmissionPolicy) throw new BadRequest("Your request contained foreign keys", true);
                continue;
            }
        }
        if(count($this->__issues) !== 0) throw new ValidationFailed("Validation failed.", $this->__issues);
        return $mutant;
    }

    private function validate_field($field, $value) {
        if($this->__excludeUnregisteredKeys) {
            if(!key_exists($field, $this->__schema)) throw new SchemaExcludesUnregisteredKeys('Schema excludes unregistered keys');
        }
        $result = $this->datatype_persistance($field, $value);
        try {
            $validated = $result->validate();
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
}