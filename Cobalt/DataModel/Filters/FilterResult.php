<?php
namespace Cobalt\DataModel\Filters;

use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\JobQueue\Interfaces\JobInterface;
use Cobalt\JobQueue\Models\Job;
use stdClass;

class FilterResult {
    public DictionaryType $model;
    readonly array $toValidate;
    readonly array $filteredValue;
    protected bool $validationComplete = false;

    protected int $filterIssueCount = 0;
    /** @var array<string,FilterIssue[]> */
    protected array $issuesByField = [];

    /** @var JobInterface $job */
    readonly JobInterface $job;

    function __construct(){
        $this->job = new Job();
    }

    private function toFieldName(Generic|string $generic) {
        return is_string($generic) ? $generic : $generic->getFieldDotNotation();
    }

    function getModel():DictionaryType {
        return $this->model;
    }

    function setModel(DictionaryType $model) {
        $this->model = $model;
        $this->job->model = $model;
    }
    
    /**
     * @return FilterIssue[]
     */
    function getIssues():array {
        // Flatten the array and return the issues as one large file
        return array_merge(...array_values($this->issuesByField));
    }
    
    /**
     * 
     * @param array<string,FilterIssue[]> $issues 
     * @return $this 
     */
    protected function setIssues(array $issues) {
        $this->issuesByField = $issues;
        $this->filterIssueCount = array_sum(array_map("count", $issues));
        return $this;
    }

    /**
     * Register an a FilterIssue for a Generic
     * @param Generic $generic 
     * @param string $publicMessage 
     * @param null|string $privateMessage 
     * @param int $code 
     * @return FilterIssue 
     */
    function addIssue(Generic $generic, string $publicMessage, ?string $privateMessage = null, int $code = 0):FilterIssue {
        $issue = new FilterIssue($generic, $publicMessage, $privateMessage, $code);
        $name = $this->toFieldName($generic);
        $this->issuesByField[$name][] = $issue;
        $this->filterIssueCount += 1;
        return $issue;
    }

    /**
     * Check if this update operation had any issues
     * @return bool 
     */
    function hasIssues():bool {
        return $this->filterIssueCount >= 1;
    }

    /**
     * Get a FilterResult for a specific field
     * @param Generic|string $generic 
     * @return FilterResult 
     */
    function resultForField(Generic|string $generic):FilterResult {
        $name = $this->toFieldName($generic);
        $result = new FilterResult();
        $result->setIssues([$name => $this->issuesByField[$name]??[]]);
        // $result->setJobs([$name => $this->jobsForField($name)]);
        return $result;
    }

    /**
     * Returns an update query array
     * @return array{'$currentDate':array,'$inc':array,'$min':array,'$max':array,'$mul':array,'$rename':array,'$set':array,'$setOnInsert':array,'$unset':array}
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