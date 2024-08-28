<?php

namespace Drivers;

class FSManager extends Database {

    public function get_collection_name() {
        return "fs.files";
    }
    public $fromDataView = "/admin/crudable/file-index-container.html";
    public function fromData($data) {
        $html = view($this->fromDataView, ['data' => $data, 'badge' => $this->thumbnailBadge($data)]);
        return $html;
    }
    
    private function thumbnailBadge($data) {
        if($data->isThumbnail) return "<i name='image-size-select-small'></i>";
        return "";
    }


    
}