<?php

namespace Cobalt\SchemaPrototypes\Traits;

use Exception;
use Exceptions\HTTP\BadRequest;

trait ImageManipulation {
    public function generateThumbnail(string $src, string $dest, int $width, ?int $height = null, $overwrite_dest = false) {
        if(file_exists($dest) && $overwrite_dest === false) throw new BadRequest("Not allowed to overwrite the destination");
        $filename = createThumbnail($src, $dest, $width, $height);
        if(file_exists($dest)) return $dest;
        throw new Exception("Failed to create the thumbnail");
    }

    // /**
    //  * @param string $src 
    //  * @return string /<dir>/<basename>.thumb.<ext>
    //  */
    // public function getThumbnailFilename(string $src):string {
    //     $ext = pathinfo($src, );
    //     return 
    // }
}
