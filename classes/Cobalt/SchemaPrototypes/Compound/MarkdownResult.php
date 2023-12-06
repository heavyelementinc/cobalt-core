<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\SchemaResult;

use Validation\Exceptions\ValidationIssue;

class MarkdownResult extends StringResult {
    protected $type = "string";
}