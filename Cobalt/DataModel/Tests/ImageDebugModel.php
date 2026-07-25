<?php

namespace Cobalt\DataModel\Tests;

use Cobalt\DataModel\Directives\Images\Thumbnail;
use Cobalt\DataModel\Types\ImageType;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\StringType;
use Override;

class ImageDebugModel extends DocumentType {
    readonly StringType $string;
    #[Thumbnail()]
    readonly ImageType $image;

    #[Override]
    public function getDefaultField(): StringType {
        return $this->string;
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "test";
    }
}