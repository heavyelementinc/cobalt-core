<?php

namespace Cobalt\DataModel\Models;

use Cobalt\DataModel\Directives\Filters\Arrays\Each;
use Cobalt\DataModel\Types\ArrayType;
use Cobalt\DataModel\Types\IdType;
use Cobalt\DataModel\Types\StringType;
use Exception;

class ImageModel extends FilesystemModel {
    readonly ImageMetaModel $details;
    readonly StringType $thumbnail;
    readonly IdType $thumbnail_id;
    readonly StringType $alt;
    // /** @var ImageModel[] $alternates */
    #[Each(new ImageModel())]
    readonly ArrayType $alternates;
    
    function toImgTag():string {
        throw new Exception("Not implemented");
    }

    function hasAlternates():bool {
        return count($this->alternates) >= 1;
    }

    function getDefault():ImageModel {
        throw new Exception("Not implemented");
    }
}