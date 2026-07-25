<?php

namespace Cobalt\DataModel\Tests;

use Cobalt\DataModel\Directives\Filters\Required;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Types\BooleanType;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\StringType;
use Override;

class BooleanDebugModel extends DocumentType {
    #[Valid([true])]
    readonly BooleanType $truthy;

    #[Valid([false])]
    readonly BooleanType $falsey;

    #[Valid([true, false])]
    readonly BooleanType $required;

    #[Override]
    public function getDefaultField(): StringType
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "test";
    }

}