<?php

namespace Cobalt\Components\Projects\Models;

use Cobalt\DataModel\Directives\DefaultValue;
use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Directives\Images\Thumbnail;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\ImageType;
use Cobalt\DataModel\Types\NumberType;

class CoverImageModel extends DictionaryType {
    #[Thumbnail()]
    readonly ImageType $image;
    #[Min(0)]
    #[Max(100)]
    #[DefaultValue(50)]
    readonly NumberType $desktop_x;
    #[Min(0)]
    #[Max(100)]
    #[DefaultValue(50)]
    readonly NumberType $desktop_y;
    #[Min(25)]
    #[Max(200)]
    #[DefaultValue(100)]
    readonly NumberType $desktop_scale;
    #[Min(0)]
    #[Max(100)]
    #[DefaultValue(50)]
    readonly NumberType $mobile_x;
    #[Min(0)]
    #[Max(100)]
    #[DefaultValue(50)]
    readonly NumberType $mobile_y;
    #[Min(25)]
    #[Max(200)]
    #[DefaultValue(100)]
    readonly NumberType $mobile_scale;
}