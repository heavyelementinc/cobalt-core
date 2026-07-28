<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Types\ImageType;

/** @mixin ImageType */
trait JobHandlerImage {
    function __thumbnail(string $target, ?int $height = null, ?int $width = null, ?string $finalLocation = null) {
        $thumb = $this->directives->thumbnail;
        
    }

    function __resize(string $target, int $height, int $width, string $finalLocation) {
        // Resize an image
    }

    function __reformat(string $target, string $format, string $finalLocation) {
        
    }


}