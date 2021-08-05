<?php
class FileController extends \Controllers\FileController {

    function __construct() {
        if (!app("enable_core_content")) throw new Exceptions\HTTP\NotFound("Shared files are not enabled.");
    }

    function locate() {
        $path = $GLOBALS['router']->uri;
        // $file = __ENV_ROOT__ . "/shared/$path";
        $file = find_one_file([__ENV_ROOT__ . "/shared/", ...$GLOBALS['SHARED_CONTENT']], $path);
        if (!file_exists($file)) throw new Exceptions\HTTP\NotFound("The resource could not be located");
        // header('Content-Description: File Transfer');
        // header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        // header('Expires: 0');
        // header('Cache-Control: must-revalidate');
        $mime = mime_content_type($file);
        if (pathinfo($file, PATHINFO_EXTENSION) === "css") $mime = "text/css";
        header('Content-Type: ' . $mime);
        // header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    function javascript($match) {
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
        header('Content-Length: ' . filesize($file));
        readfile($file);
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
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }


    function plugin_resources($plugin, $match) {
        $content_dirs = [];

        if (!isset($GLOBALS['ACTIVE_PLUGINS'][$plugin])) throw new \Exceptions\HTTP\NotFound("The resource could not be located");
        $plugin = $GLOBALS['ACTIVE_PLUGINS'][$plugin];
        // foreach ($GLOBALS['ACTIVE_PLUGINS'] as $i => $plugin) {
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
        readfile($file);
    }
}
