<?php

use Controllers\ClientFSManager;

class FileController extends \Controllers\FileController {
    use ClientFSManager;
    function __construct() {
        if (!app("enable_core_content")) throw new Exceptions\HTTP\NotFound("Shared files are not enabled.");
        $cacheControl = 'Cache-Control: private, ';
        if (!app("debug")) $cacheControl .= "immutable, ";
        $cacheControl .= "max-age=31536000";
        header($cacheControl);
        header('Pragma: private');
        header('Last-Modified: Sat, 26 Oct 1985 08:15:00 GMT');
        $expires = gmdate("D, d M Y H:i:s", strtotime("+30 days"));
        header("Expires: $expires");
    }

    function core_content_shared() {
        global $ROUTER;
        global $SHARED_CONTENT;
        $path = $ROUTER->uri;
        // $file = __ENV_ROOT__ . "/shared/$path";
        $file = find_one_file([
            __APP_ROOT__ . "/shared/",
            __ENV_ROOT__ . "/shared/",
            ...$SHARED_CONTENT
        ], sanitize_path_name($path));
        if (!file_exists($file)) throw new Exceptions\HTTP\NotFound("The resource could not be located");
        // header('Content-Description: File Transfer');
        // header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        // header('Expires: 0');
        $mime = mime_content_type($file);
        if (pathinfo($file, PATHINFO_EXTENSION) === "css") $mime = "text/css";
        header('Content-Type: ' . $mime);
        // header('Pragma: public');
        $this->get_etag($file);
        readfile($file);
        exit;
    }

    function javascript($match) {
        $match = sanitize_path_name($match);
        $cache = new \Cache\Manager("js-precomp/$match");
        if ($cache->exists) {
            $file = $cache->file_path;
        } else {
            $files = files_exist([
                __APP_ROOT__ . "/src/$match",
                __ENV_ROOT__ . "/src/$match"
            ], false);
            if (!count($files))  throw new \Exceptions\HTTP\NotFound("The resource could not be located");
            $file = $files[0];
        }

        header("Content-Type: application/javascript;charset=UTF-8");
        $this->get_etag($file);
        readfile($file);
        exit;
    }

    function css($match) {
        $cache = new \Cache\Manager("css-precomp/$match");
        if ($cache->exists) {
            $file = $cache->file_path;
        } else {
            $file = __ENV_ROOT__ . "/shared/css/$match";
            $file_exists = file_exists($file);
            if (!$file_exists)  throw new \Exceptions\HTTP\NotFound("The resource could not be located");
        }

        header("Content-Type: text/css;charset=UTF-8");
        $this->get_etag($file);
        readfile($file);
        exit;
    }


    function plugin_resources($plugin, $match) {
        global $ACTIVE_PLUGINS;
        $content_dirs = [];

        if (!isset($ACTIVE_PLUGINS[$plugin])) throw new \Exceptions\HTTP\NotFound("The resource could not be located");
        $plugin = $ACTIVE_PLUGINS[$plugin];
        // foreach ($ACTIVE_PLUGINS as $i => $plugin) {
        //     array_push($content_dirs, $plugin->register_public_content_dir());
        // }

        // $file = find_one_file($content_dirs, $match);
        $file = $plugin->register_public_content_dir() . $match;
        if (!$file) throw new \Exceptions\HTTP\NotFound("The resource could not be located");

        $mime = mime_content_type($file);

        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case "css":
                $mime = "text/css";
                break;
            case "js":
                $mime = "application/javascript;charset=UTF-8";
                break;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        $this->get_etag($file);
        readfile($file);
        exit;
    }

    function manifest() {
        $content = with("/parts/manifest.site");
        header('Content-Type: text/json');
        header('Content-Length: ' . strlen($content) * 8);
        echo $content;
        exit;
    }

    function get_etag($path) {
        $mbThreshold = 25600;
        $filesize = filesize($path);
        $header = "ETag: \"";
        if ($filesize < $mbThreshold) $header .= md5_file($path);
        $header .= app('version') . "\"";
        header($header);
        header('Content-Length: ' . $filesize);
        // exit;
    }
}
