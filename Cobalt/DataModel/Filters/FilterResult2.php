<?php

namespace Cobalt\DataModel\Filters;

use Cobalt\DataModel\Types\Generic;

/**
 * @property mixed $toValidate - The data intended to be validated
 * @property mixed $filtered - The data that has been filtered
 * @package Cobalt\DataModel\Filters
 */
class FilterResult2 {
    protected Generic $generic;
    protected mixed $filteredValue;
    protected mixed $toValidate;
    protected bool $validationComplete = false;
    /**
     * @var array<int,FilterIssue> $filterIssues
     */
    protected array $filterIssues = [];
    protected bool $abortFurtherFilterChecks = false;
    function __get($name) {
        switch($name) {
            case 'toValidate':
                return $this->toValidate;
            case 'value':
            case 'filtered':
                return $this->filteredValue;
            default:
                return null;
        }
    }
    function __set($name, $value) {
        switch($name) {
            case 'toValidate':
                $this->toValidate = $value;
            case 'value':
            case 'filtered':
                $this->filteredValue = $value;
                break;
            default:
                return null;
        }
    }
    function getGeneric():Generic {
        return $this->generic;
    }

    function setGeneric(Generic $generic) {
        $this->generic = $generic;
    }

    function hasIssues():bool {
        return !empty($this->filterIssues);
    }

    /**
     * @return array<int,FilterIssue>
     */
    function getIssues():array {
        return $this->filterIssues;
    }

    function testFilter(mixed $toValidate):self {
        $this->validationComplete = true;
        try {
            $this->filteredValue = $this->generic->filter($toValidate);
        } catch (FilterIssue $e) {
            $this->filterIssues[] = $e;
        }
        return $this;
    }
}