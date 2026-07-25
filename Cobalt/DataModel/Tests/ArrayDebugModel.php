<?php

namespace Cobalt\DataModel\Tests;

use Cobalt\DataModel\Directives\Filters\Arrays\Each;
use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Types\ArrayType;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\NumberType;
use Cobalt\DataModel\Types\StringType;
use Override;

class ArrayDebugModel extends DocumentType {

    #[Min(2)]
    readonly ArrayType $min;
    #[Max(5)]
    readonly ArrayType $max;
    
    #[Each(new StringType())]
    readonly ArrayType $ofStrings;

    #[Each(new NumberType())]
    readonly ArrayType $ofNumbers;

    #[Each(new BooleanDebugModel())]
    readonly ArrayType $ofModels;

    #[Override]
    public function getDefaultField(): StringType
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getCollectionName($string = null): string
    {
        throw new \Exception('Not implemented');
    }
    
}