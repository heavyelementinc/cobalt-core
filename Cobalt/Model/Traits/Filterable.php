<?php

namespace Cobalt\Model\Traits;

use Cobalt\Maps\Exceptions\SchemaExcludesUnregisteredKeys;
use Cobalt\Model\Classes\ValidationResults\MergeResult;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Error;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Reflection;
use ReflectionFunction;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

trait Filterable {
    protected array $__dataset = [];
    protected array $__issues = [];
    protected array $__schema = [];
    protected array $__validatedFields = [];
    protected bool $__excludeUnregisteredKeys = __APP_SETTINGS__['Validation_exclude_unregistered_keys_by_default'];
    protected bool $__strictDataSubmissionPolicy = __APP_SETTINGS__['Validation_strict_data_submission_policy_by_default'];
    protected bool $__datasetValidated = false;
    protected $schema;

    abstract protected function __defineSchema(array $schema):void;
    abstract protected function hydrate(array &$target, string|int $field_name, $value, ?GenericModel $model = null, $name = null, ?array $directives = [], ?MixedType $instance = null):void;
    abstract public function setData(array|BSONDocument|BSONArray $data): void;

    public function __filter(array $toValidate):self {
        if($this->__schema) $this->__defineSchema([]);

        $toValidate = array_undot($toValidate);

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
        $this->setData($this->__validatedFields);
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
    public function __modify(string $name, mixed $value, bool $validateBeforeModification = true, ?array &$target = null):void {
        if($validateBeforeModification) $value = $this->__validate_field($name, $value);
        $this->__validatedFields[$name] = $value;
    }

    protected function __validate_field($field, $value) {
        if($this->__excludeUnregisteredKeys) {
            if(!key_exists($field, $this->__schema)) throw new SchemaExcludesUnregisteredKeys('Schema excludes unregistered keys');
        }
        $r = [];
        $this->hydrate($r, $field, $value, $this, $field, $this->__schema[$field], $this->__schema[$field]['type']);
        $result = $r[$field];
        try {
            if($value === null || $value === "") {
                if($result->isRequired()) throw new ValidationIssue("This field is required");
                throw new ValidationContinue("This field is empty and it's not required. Continuing.");
            }

            // This is disabled because the filter directive is called later
            if(key_exists('filter', $this->__schema[$field]) && is_callable($this->__schema[$field]['filter'])) {
                $funcReflection = new ReflectionFunction($this->__schema[$field]['filter']);
                $argsReflection = $funcReflection->getParameters();
                if(!$argsReflection[0]->isPassedByReference()) {
                    throw new Error("The filter directive specified for field `$field` must accept values passed only by reference!");
                }
                $returnType = $funcReflection->getReturnType();
                if((string)$returnType !== "void") {
                    throw new Error("The filter directive specified for field `$field` must specify a return type of `void`!");
                }
                $this->__schema[$field]['filter']($value);
            }

            if($result->hasDirective("pattern")) {
                $pattern = $result->getDirective("pattern");
                if($pattern) $this->testPattern($result, $value, $pattern);
            }
            $validated = $result->filter($value);
            if(key_exists('set', $this->__schema[$field]) && is_callable($this->__schema[$field]['set'])) {
                $validated = $result->set($value);
            }
        } catch (ValidationContinue $e) {
            // If we catch a ValidationContinue, let's throw it again so
            // we know to skip this field when we recieve this signal
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
        
        if($validated instanceof MergeResult) {
            foreach($validated->get_value() as $keypath => $value) {
                $this->__modify($keypath, $value, false);
            }
        } else {
            $this->__modify($field, $validated, false);
        }
    }

    public function set_unregistered_key_state(bool $value):void {
        $this->__excludeUnregisteredKeys = $value;
    }

    public function setValidationState(bool $state): void {
        $this->__datasetValidated = $state;
    }

    public function getValidationState():bool {
        return $this->__datasetValidated;
    }

    public function __operators($allowUnvalidated = false, &$result):void {
        // $result = [];
        if(!$this->getValidationState() && $allowUnvalidated === false) throw new ValidationFailed("Server configuration error");
        /**
         * @var string $field -> The name of the field
         * @var string $value -> The validated value
         */
        foreach($this->__validatedFields as $field => $value) {
            
            /** @var MixedType $target */
            $target = null;
            if(key_exists($field,$this->__dataset)) $target = $this->__dataset[$field];

            $operator = '$set';
            if($target->hasDirective("operator")) {
                $target->getDirective("operator", $result, $field, $value);
                continue;
            }
            if(!key_exists($operator,$result)) $result[$operator] = [];
            $result[$operator][$field] = $value;
        }

        // return $result;
    }

    public function getValidatedFields():array {
        return $this->__validatedFields;
    }
}


// $value->__getStorable();
// $v = $this->__hydrated[$field];
// $schema = $v->getSchema();
// $storable = $v->__getStorable();

// if(!key_exists('operator', $schema)) {
//     if(!key_exists('$set', $result)) $result['$set'] = [];
//     $result['$set'][$this->__namePrefix.$field] = $storable;
//     continue;
// }
// $operator = $schema['operator'];

// switch(gettype($operator)) {
//     case "string":
//         if(!key_exists($operator, $result)) $result[$operator] = [];
//         $result[$operator][$this->__namePrefix.$field] = $storable;
//         break;
//     case is_callable($operator):
//         $r = $operator($field, $storable);
//         $operator = key($r);
//         if(!key_exists($operator, $result)) $result[$operator] = [];
//         $result[$operator] = array_merge($result[$operator], $r[$operator]);
// }