<?php

namespace Components\ServiceAreas\Models;

use Cobalt\DataModel\Directives\Types\Composite\MarkdownType;
use Cobalt\DataModel\Types\BooleanType;
use Cobalt\DataModel\Types\DataModel;
use Cobalt\DataModel\Types\ImageType;
use Cobalt\DataModel\Types\StringType;
use Override;

class ServiceAreaCounty extends DataModel {
    readonly StringType $county_name;
    readonly StringType $slug;
    readonly StringType $href;
    readonly StringType $location;
    readonly ImageType $img;
    readonly StringType $credit;
    readonly MarkdownType $blurb;
    readonly BooleanType $include;

    #[Override]
    public function getDefaultField(): StringType {
        return $this->county_name;
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "ServiceAreaCounties";
    }
}
