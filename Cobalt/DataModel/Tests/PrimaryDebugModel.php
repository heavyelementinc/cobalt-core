<?php

namespace Cobalt\DataModel\Tests;

use Cobalt\DataModel\Types\DataModel;
use Cobalt\DataModel\Directives\DefaultValue;
use Cobalt\DataModel\Directives\ReferenceModel;
use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Directives\Filters\Nullable;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Types\ArrayType;
use Cobalt\DataModel\Types\BooleanType;
use Cobalt\DataModel\Types\ForeignDocumentType;
use Cobalt\DataModel\Types\NumberType;
use Cobalt\DataModel\Types\StringType;
use Override;

class PrimaryDebugModel extends DataModel {
    #[DefaultValue(['one' => 'One'])]
    readonly ArrayType $array;

    #[DefaultValue(true)]
    readonly BooleanType $bool;

    #[ReferenceModel(new StringDebugModel)]
    readonly ForeignDocumentType $foreign;

    #[DefaultValue(1)]
    #[Min(5)]
    #[Max(12)]
    readonly NumberType $number;

    readonly StringType $string;

    // readonly StringDebugModel $stringDebugModel;

    #[Override]
    public function getDefaultField(): StringType {
        return $this->title;
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "test";
    }
}
