<?php

namespace Controllers;

use Exceptions\HTTP\NotFound;

trait ClientDownloadFile {
    final public function clientDownloadFile($path): void {
        ob_clean();
        
        if(gettype($path) === "string") {
            // The path to the file you want to send
            $path = $path;

            // The file name of the download, change this if needed
            $public_name = basename($path);

            // get the file's mime type to send the correct content type header
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $path);
            $file_size = filesize($path);
        } else {
            $file = $path;
            if($file === null) throw new NotFound("File was not found");
            $public_name = $file->filename;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
        }

        

        // send the headers
        // header("Content-Disposition: attachment; filename=$public_name;");
        header("Content-Type: $mime_type");
        header("Content-Length: $file_size");

        $headers = getallheaders();

        // stream the file
        $fp = fopen($path, 'rb');
        if(isset($headers['Range'])) {
            $range = $headers['Range'];
            fseek($fp,$range);
        }
        fpassthru($fp);
        exit;
    }
}