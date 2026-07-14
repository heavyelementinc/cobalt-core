<?php

namespace Cobalt\DataModel\Filters;

use Cobalt\DataModel\Types\Generic;
use TypeError;

class FilterFailed extends FilterIssue {
    /**
     * @var array<int,array{generic:Generic,issue:FilterIssue}>
     */
    protected array $fields = [];
    function addFailedField(Generic $generic, FilterIssue $issue) {
        $this->fields[] = [
            'generic' => $generic,
            'issue'   => $issue,
        ];
    }

    function unwrap(FilterFailed $failed) {
        array_push($this->fields, ...$failed->getFields());
    }

    function count() {
        return count($this->fields);
    }

    /**
     * Returns the values of a failed filter process
     * @return array<int,array{generic:Generic,issue:FilterIssue}>
     */
    function getFields():array{
        return $this->fields;
    }
}