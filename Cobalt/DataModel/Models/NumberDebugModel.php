<?php

namespace Cobalt\DataModel\Models;

use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Directives\Filters\Pattern;
use Cobalt\DataModel\Types\ModelType;
use Cobalt\DataModel\Types\NumberType;
use Cobalt\DataModel\Types\StringType;
use Override;

class NumberDebugModel extends ModelType {

    #[Min(1)]
    readonly NumberType $min;
    #[Max(10)]
    readonly NumberType $max;

    #[Pattern("/\d{1}e\d{1}/")]
    readonly NumberType $pattern;

    #[Pattern("/\d{2}/")]
    readonly NumberType $allDigitPattern;

    #[Override]
    public function getDefaultField(): StringType {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "test";
    }

}