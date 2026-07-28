<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Types\Generic;

/**
 * @mixin Generic
 */
trait FileHandlerAudio {
    use FileHandlerGeneric;
    
    public function getAudioMetadata($path_to_file, $mime_type = null) {
        if(!$mime_type) $mime_type = $this->getMimeType($path_to_file);
        
        $id3 = new \getID3();
        $info = $id3->analyze($path_to_file);
        
        $meta = $info['audio'];
        $meta['mimetype'] = $mime_type;
        $meta['seconds'] = $info['playtime_seconds'];
        return $meta;
    }
}