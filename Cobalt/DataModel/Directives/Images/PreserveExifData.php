<?php

namespace Cobalt\DataModel\Directives\Images;

use Cobalt\DataModel\Directives\Base\AbstractBoolDirective;
use Exception;

class PreserveExifData extends AbstractBoolDirective {
    function strip_exif_data(string $path_to_file) {
        // $img = new Imagick(realpath($path_to_file));
        throw new Exception("Not implemented");
    }
}