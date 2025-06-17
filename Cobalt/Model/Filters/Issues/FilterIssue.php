<?php

namespace Cobalt\Model\Filters\Issues;

use Exception;

class FilterIssue extends Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}