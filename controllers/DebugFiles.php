<?php

use Controllers\ClientFSManager;
use Controllers\Controller;

class DebugFiles {
    use ClientFSManager;

    function file_upload_page() {
        add_vars([
            'title' => 'FileSystem Test',
            'database_file_links' => $this->directoryListing('/debug/file-upload/download-test/'),
            'gallery_file_links' => $this->directoryListing('/debug/file-upload/download-test/','gallery')
        ]);
        
        set_template("/debug/file-upload-test.html");
    }

    function simple_file_upload() {
        if($_POST['thumbnail'] === true) {
            return $this->clientUploadImageThumbnail('test',0,200);
        } 
        return $this->clientUploadFile('test', 0);
    }

    function multi_file_upload() {
        if($_POST['thumbnail'] === true) {
            return $this->clientUploadImagesAndThumbnails('test',200);
        } 
        return $this->clientUploadFiles("test");
    }

    function extra_metadata() {
        return $this->clientUploadFiles("test",['arbitrary_data' => $_POST['arbitrary_data']]);
    }

}