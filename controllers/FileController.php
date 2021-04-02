<?php
class FileController extends \Controllers\FileController{

    function __construct(){
        if(!app("enable_core_content")) throw new Exceptions\HTTP\NotFound("Shared files are not enabled.");
    }

    function locate(){
        $path = $GLOBALS['router']->uri;
        $file = __ENV_ROOT__ . "/shared/$path";
        if(!file_exists($file)) throw new Exceptions\HTTP\NotFound("The resource could not be located");
        // header('Content-Description: File Transfer');
        // header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        // header('Expires: 0');
        // header('Cache-Control: must-revalidate');
        $mime = mime_content_type($file);
        if(pathinfo($file,PATHINFO_EXTENSION) === "css") $mime = "text/css";
        header('Content-Type: ' . $mime);
        // header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    function javascript($match){
        $cache = new \Cache\Manager("js-precomp/$match");
        if($cache->exists) {
            $file = $cache->path_name;
        } else {
            $files = files_exist([
                __APP_ROOT__ . "/private/js/$match",
                __ENV_ROOT__ . "/js/$match"
            ],false);
            if(!count($files))  throw new \Exceptions\HTTP\NotFound("The resource could not be located");
            $file = $files[0];
        }

        header("Content-Type: application/javascript;charset=UTF-8");
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }
}