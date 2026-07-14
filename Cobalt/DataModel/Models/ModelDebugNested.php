<?php

namespace Cobalt\DataModel\Models;

use Cobalt\DataModel\Types\ModelType;
use Cobalt\DataModel\Types\StringType;
use Override;

class ModelDebugNested extends ModelType {
    readonly BooleanDebugModel $bools;
    readonly NumberDebugModel $numbers;
    readonly StringDebugModel $strings;

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