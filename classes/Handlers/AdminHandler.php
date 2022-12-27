<?php

/**
 * Admin Handler
 * 
 * This handler class should contain only that which is needed by the Cobalt
 * engine to handle Admin pages.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Handlers;

use \Cache\Manager as CacheManager;

class AdminHandler extends WebHandler {
    var $route_table_cache = "js-precomp/admin-router-table.js";
    // var $script_cache_name = "template-precomp/admin-script.html";
    // function post_router_init(){
    //     $this->prepare_html_framework();
    // }
    function auth_panel() {
        if (!session_exists()) return "";
        $panel = "<link rel='stylesheet' href='/core-content/css/admin-panel.css?{{app.version}}'>";
        $panel .= "<nav id='admin-panel'>";
        
        $panel .= get_route_group("admin_panel", [
            'prefix' => app("context_prefixes")['admin']['prefix'],
            'ulSuffix' => "<a href='/admin/settings/'><i name='cog'></i> Settings</a>",
        ]);
        $session = session();
        $panel .= "<div id='user-panel-header'>".$session->{'name'}."</div>";
        $panel .= "</nav>";
        return $panel;
    }

    var $header_template = "/parts/admin-header.html";
    var $footer_template = "/parts/admin-footer.html";
    var $meta_selector = "admin";

    // function generate_style_meta() {
    //     $link_tags = "";
    //     $compiled = "";
    //     $debug = app("debug");
    //     foreach (array_merge(app('common-css-packages'), app('admin-css-packages')) as $package) {
    //         $files = files_exist([
    //             __APP_ROOT__ . "/shared/css/$package",
    //             __APP_ROOT__ . "/public/res/css/$package",
    //             __ENV_ROOT__ . "/shared/css/$package"
    //         ]);
    //         if ($debug === true) {
    //             $path = "/res/css/";
    //             if (strpos($files[0], "/shared/css/")) $path = "/core-content/css/";
    //             $link_tags .= "<link rel=\"stylesheet\" href=\"$path$package?{{app.version}}\">";
    //         } else {
    //             $compiled .= "\n\n" . file_get_contents($files[0]);
    //         }
    //     }

    //     foreach ($GLOBALS['PACKAGES']['css'] as $public => $private) {
    //         $file = file_exists($private);
    //         if (!$file) continue;
    //         if ($debug === true) {
    //             $link_tags .= "<link rel=\"stylesheet\" href=\"$public?{{app.version}}\">";
    //         } else {
    //             $compiled .= "\n\n" . file_get_contents($file);
    //         }
    //     }
    //     if ($link_tags === "") $link_tags = "<link rel=\"stylesheet\" href=\"/core-content/css/admin.css?{{app.version}}\">";

    //     if ($compiled !== "") {
    //         $minifier = new \MatthiasMullie\Minify\CSS();
    //         $minifier->add($compiled);
    //         $compiled = $minifier->minify();

    //         $cache = new CacheManager("css-precomp/package.css");
    //         $cache->set($compiled, false);
    //     }
    //     return $link_tags;
    // }

}
