<?php

namespace Cobalt\DataModel\Tests;

use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\StringType;
use Override;

class ModelDebugNested extends DocumentType {
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