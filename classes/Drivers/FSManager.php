<?php

namespace Drivers;

class FSManager extends Database {

    public function get_collection_name() {
        return "fs.files";
    }

    public function fromData($data) {
        $html = "<div class='fs-filemanager' data-id='$data->_id' data-thumbnail-id='$data->thumbnail_id' 
            style='--contrast-color: ".$data->meta->contrast_color."; --accent-color: ".$data->meta->accent_color.";'>
            <div class='badges'>".
            $this->thumbnailBadge($data)
            ."</div>
            <img src='/res/fs/$data->filename' onclick='shadowbox(this)' data-shadowbox-group='group'>
        </div>
        ";
        return $html;
    }
    
    private function thumbnailBadge($data) {
        if($data->isThumbnail) return "<i name='image-size-select-small'></i>";
        return "";
    }


    
}