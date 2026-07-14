<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Classes\DirectiveList;
use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Filters\FilterResult;
use Cobalt\DataModel\Filters\FilterSkip;
use Cobalt\DataModel\Types\Generic;

trait GenericFilters {
    protected DirectiveList $directives;
    public readonly FilterResult $filterResult;

    protected bool $__isModified = false;

    public function isModified():bool {
        return $this->__isModified;
    }

    /**
     * Set the canonical value for this object
     * @param mixed $value 
     * @return void 
     */
    abstract public function setValue(mixed $value):void;
    /**
     * Use this function to signal a value you want to update on a database 
     * update query. You may call this function from in another field's filter
     * directive: $this->rootModel->other_field->updateValue('somevalue')
     * @param mixed $value 
     * @return void 
     */
    public function updateValue(mixed $value) {
        $this->setValue($value);
        $this->__isModified = true;
    }
    /**
     * This function is called when it's time to validate a value
     * for storage in the database.
     * 
     * @param mixed $toValidate 
     * @return mixed The filtered and validated value fit for storage
     */
    abstract function filter(mixed $toValidate):mixed;

    // Why use a wrapper method? Ease of implementation on the coder side of things.
    function __filter(mixed $toValidate):FilterResult {
        $result = $this->filter($toValidate);
        $update = $this->filterResult->resultForField($this);
        if($update->hasIssues()) {
            return $update;
        }
        // Set the value of this Generic for later serialization
        $this->updateValue($result);
        return $update;
    }

    function isNullable(mixed $toValidate):mixed {
        $nullable  =  $this->directives->nullable?->value ?? false;
        $clearable = $this->directives->clearable?->value ?? false;
        if($toValidate === null && $nullable == false && $clearable == false) {
            // throw new FilterIssue("Cannot be null");
            $this->filterResult->addIssue($this, "Cannot be null");
        }
        return $nullable || $clearable;
    }

    function filter_pattern(mixed $toValidate):mixed {
        if($this->directives->pattern?->value && !preg_match($this->directives->pattern->value, $toValidate)) {
            // throw new FilterIssue(sprintf($this->directives->pattern->getFailureMessage()));
            $this->filterResult->addIssue($this, sprintf($this->directives->pattern->getFailureMessage()));
        }
        return $toValidate;
    }
    /**
     * Returns an array
     * @param array{'$currentDate':array,'$inc':array,'$min':array,'$max':array,'$mul':array,'$rename':array,'$set':array,'$setOnInsert':array,'$unset':array} $updateArray
     */
    function toUpdateQueryArray(array &$updateArray) {
        $updateArray['$set'][$this->getFieldDotNotation()] = $this->serialize();
    }
}