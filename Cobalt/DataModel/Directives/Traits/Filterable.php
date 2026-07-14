<?php

namespace Cobalt\DataModel\Directives\Filters;

use Cobalt\DataModel\Filters\FilterIssue;

trait FilterableDirective {
    /**
     * @throws FilterIssue
     * @param mixed $toValidate 
     * @return mixed 
     */
    abstract function filter(mixed $toValidate):mixed;
}