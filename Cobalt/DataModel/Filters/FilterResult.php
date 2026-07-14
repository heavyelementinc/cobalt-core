<?php
namespace Cobalt\DataModel\Filters;

use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\ModelType;
use stdClass;

class FilterResult {
    protected DictionaryType $model;
    readonly array $toValidate;

    readonly array $filteredValue;
    
    protected bool $validationComplete = false;

    /**
     * @return FilterIssue[]
     */
    protected int $filterIssueCount = 0;
    protected array $issuesByField = [];

    function setModel(DictionaryType $model) {
        $this->model = $model;
    }

    function getModel():DictionaryType {
        return $this->model;
    }

    function hasIssues():bool {
        return $this->filterIssueCount >= 1;
    }

    function addIssue(Generic $generic, string $publicMessage, ?string $privateMessage = null, int $code = 0):FilterIssue {
        $issue = new FilterIssue($generic, $publicMessage, $privateMessage, $code);
        $this->issuesByField[$generic->getFieldDotNotation()][] = $issue;
        $this->filterIssueCount += 1;
        return $issue;
    }

    protected function setIssues(array $issues) {
        $this->issuesByField = $issues;
        $this->filterIssueCount = count($this->getIssues());
        return $this;
    }

    function resultForField(Generic $generic):FilterResult {
        $name = $generic->getFieldDotNotation();
        $result = new FilterResult();
        $result->setIssues([$name => $this->issuesByField[$name]??[]]);
        return $result;
    }

    /**
     * @return FilterIssue[]
     */
    function getIssues():array {
        // Flatten the array and return the issues as one large file
        return array_merge(...array_values($this->issuesByField));
    }

    /**
     * Returns an update query array
     * @param array{'$currentDate':array,'$inc':array,'$min':array,'$max':array,'$mul':array,'$rename':array,'$set':array,'$setOnInsert':array,'$unset':array} $updateArray
     */
    function getUpdateDocument():array {
        $updateArray = [];
        /** @var Generic $value */
        foreach($this->model as $field => $value) {
            if($value->isModified()) $value->toUpdateQueryArray($updateArray);
        }
        return $updateArray;
    }
}