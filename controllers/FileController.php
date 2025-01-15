<?php

use Cobalt\Extensions\Extensions;
use Cobalt\Notifications\PushNotifications;
use Controllers\ClientFSManager;
use Exceptions\HTTP\NotFound;

class FileController extends \Controllers\FileController {
    use ClientFSManager;
    function __construct() {
        if (!app("enable_core_content")) throw new Exceptions\HTTP\NotFound("Shared files are not enabled.");
        $cacheControl = 'Cache-Control: private, ';
        $cacheControl .= "immutable, ";
        $cacheControl .= "max-age=31536000";
        if(config()['mode'] === COBALT_MODE_DEVELOPMENT) $cacheControl = "Cache-Control: no-cache";
        header($cacheControl);
        header('Pragma: private');
        $expires = gmdate("D, d M Y H:i:s", strtotime("+30 days"));
        header("Expires: $expires");
    }

    function core_content_shared() {
        global $ROUTER;
        global $SHARED_CONTENT;
        $path = $ROUTER->uri;
        $extensions = [];
        Extensions::invoke("register_shared_dir", $extensions);
        // $file = __ENV_ROOT__ . "/shared/$path";
        $file = find_one_file([
            __APP_ROOT__ . "/shared/",
            ...$extensions ?? [],
            __ENV_ROOT__ . "/shared/",
            ...$SHARED_CONTENT
        ], sanitize_path_name($path));
        if (!file_exists($file)) throw new Exceptions\HTTP\NotFound("The resource could not be located");
        // header('Content-Description: File Transfer');
        // header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        // header('Content-Disposition: inline');
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
            $extensions = [];
            Extensions::invoke("register_js_dirs", $extensions);
            $file = find_one_file([
                __APP_ROOT__ . "/src/",
                ...$extensions,
                __ENV_ROOT__ . "/src/",
            ], $match);
            if (!$file)  throw new \Exceptions\HTTP\NotFound("The resource could not be located");
        }

        header("Content-Type: application/javascript;charset=UTF-8");
        $this->get_etag($file);
        readfile($file);
        exit;
    }

    function service_worker() {
        $file = find_one_file([
            __APP_ROOT__ . "/src/",
            __ENV_ROOT__ . "/src/"
        ], "ServiceWorker.js");

        header("Content-Type: application/javascript;charset=UTF-8");
        $this->get_etag($file);
        readfile($file);
        exit;
    }

    function vapid_pub_key(){
        header("Content-Type: application/json;charset=UTF-8");
        echo json_encode((new PushNotifications())->vapid_keys->keyset->publicKey);
        exit;
    }

    function css($match) {
        $cache = new \Cache\Manager("css-precomp/$match");
        if ($cache->exists) {
            $file = $cache->file_path;
        } else {
            // $file = __ENV_ROOT__ . "/shared/css/$match";
            $file = find_one_file([
                __APP_ROOT__."/shared/css/",
                __ENV_ROOT__."/shared/css/",
            ],$match);
            if (!$file)  throw new \Exceptions\HTTP\NotFound("The resource could not be located");
        }

        header("Content-Type: text/css;charset=UTF-8");
        $this->get_etag($file);
        readfile($file);
        exit;
    }

    function css_versioned($version, $match) {
        $cache = new \Cache\Manager("css-precomp/v$version/$match");
        if ($cache->exists) {
            $file = $cache->file_path;
        } else {
            // $file = __ENV_ROOT__ . "/shared/css/v2/$match";
            $file = find_one_file([
                __APP_ROOT__."/shared/css_v$version/",
                __ENV_ROOT__."/shared/css_v$version/",
            ],$match);
            // $file = "/shared/css_v$version/$match";
        }

        header("Content-Type: text/css;charset=UTF-8");
        $this->get_etag($file);
        // echo view($file);
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
        $content = view("/parts/site.webmanifest");
        header('Content-Type: text/json');
        header('Content-Length: ' . strlen($content) * 8);
        echo $content;
        exit;
    }

    function get_etag($path) {
        header('Last-Modified: '. date("r",filemtime($path)));
        $mbThreshold = 25600;
        $filesize = filesize($path);
        $header = "ETag: \"";
        if ($filesize < $mbThreshold) $header .= md5_file($path);
        $header .= app('version') . "\"";
        header($header);
        header('Content-Length: ' . $filesize);

        // exit;
    }

    function robots() {
        $file = find_one_file([
            __APP_ROOT__ . "/templates/",
            // ...$extensions ?? [],
            __ENV_ROOT__ . "/templates/",
            // ...$SHARED_CONTENT
        ], "robots.txt");

        if(!$file) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);

        $ai_bots = "";
        // if(__APP_SETTINGS__["Robots_txt_block_known_ai_crawlers"]) $ai_bots = view("known-ai-robots.txt");
        $view = view("robots.txt", ['ai_bots' => $ai_bots]);
        header('Content-Length: ' . strlen($view));
        header('Content-Type: text');
        echo $view;
        exit;
    }

    function ai() {
        $file = find_one_file([
            __APP_ROOT__ . "/templates/",
            // ...$extensions ?? [],
            __ENV_ROOT__ . "/templates/",
            // ...$SHARED_CONTENT
        ], "ai.txt");

        if(!$file) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);

        $ai_bots = "";
        // if(__APP_SETTINGS__["Robots_txt_block_known_ai_crawlers"]) $ai_bots = view("known-ai-robots.txt");
        $view = view("ai.txt", ['ai_bots' => $ai_bots]);
        header('Content-Length: ' . strlen($view));
        header('Content-Type: text');
        echo $view;
        exit;
    }

    function sitemap() {
        global $ROUTER;
        $html = "";
        foreach($ROUTER->routes['web']['get'] as $route => $data) {
            $includeRawRoute = true;
            $registered = null;
            if($data['original_path'] === "/portfolio/type/{type}") {
                if(false) {
                }
            }
            if(isset($data['sitemap'])) {
                if($data['sitemap']['children']) $registered = $data['sitemap']['children'];
                if($data['sitemap']['ignore']) $includeRawRoute = !$data['sitemap']['ignore'];
            }
            if($includeRawRoute) $html .= $this->generate_site_map_entry($route, $data);
            if($registered) {
                if(is_callable($registered)) $html .= $registered();
                else $html .= $registered;
            }
        }
        $doc = view("sitemap/sitemap.xml", ['urls' => $html]);
        header('Content-Length: ' . strlen($doc));
        header('Content-Type: application/xml');
        echo $doc;
        exit;
    }
    
    private function generate_site_map_entry($route, $data) {
        $location = $data['anchor']['href'];
        $priority = $data['anchor']['order'];
        if(!$location) {
            foreach($data['navigation'] as $d => $value) {
                if(!is_array($value)) continue;
                if(key_exists('priority', $value)) $priority = $value;
                if(key_exists('href', $value)) $location = $value['href'];
            }
        }
        if(!$location) {
            $fc = 'FileController';
            if(strpos($data['original_path'], "{") !== false) {
                return "";
            } else if (substr($data['controller'], 0, strlen($fc)) === $fc) {
                return "";
            } else {
                $location = $data['original_path'];
            }
        }

        $lastmod = $data['sitemap']['lastmod'];
        if(is_callable($lastmod)) $lastmod = $lastmod();
        if(!$lastmod) {
            $controller = explode("@", $data['controller'])[0];
            try {
                $file = get_controller($controller, false, true);
            } catch(Exception $e) {
                return "";
            }
            $modifiedTime = filemtime($file);
            $lastmod = date("Y-m-d", $modifiedTime);
        }

        if(!$priority) {
            $priority = $data['nat_order'];
        }
        return view("sitemap/url.xml", [
            'server_name' => server_name(),
            'location' => $location,
            'lastModified' => $lastmod,
            'priority' => $priority + 1,
            'route' => $route,
            'data' => $data
        ]);
    }

    private function generate_blog_post_entry($data) {
        
    }
    
}
