<?php

namespace Cobalt\DataModel\Models;

use Cobalt\DataModel\Types\ColorType;
use Cobalt\DataModel\Types\NumberType;

class ImageMetaModel extends FilesystemMetaModel {
    const WIDTH = 0;
    const HEIGHT = 1;
    readonly NumberType $width;
    readonly NumberType $height;
    readonly ColorType $accent_color;
    readonly ColorType $secondary_color;
    readonly ColorType $contrast_color;
}