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
        $panel .= get_route_group("admin_panel", ['prefix' => app("context_prefixes")['admin']['prefix']]);
        // $admin_prefix = app("context_prefixes")['admin']['prefix'];
        // foreach ($GLOBALS[$GLOBALS['ROUTE_TABLE_ADDRESS']]['get'] as $route) {
        //     if ($route['panel_name'] === null) continue;
        //     $path = substr($route['original_path'], 1);
        //     $panel .= "<li><a href='$admin_prefix$path'>$route[panel_name]</a></li>";
        // }
        if (app("Plugin_enable_plugin_support")) {
            $panel .= "<h3 style='background:transparent; color:inherit'>Plugins</h3>";
            $panel .= get_route_group("admin_plugins", ['prefix' => app("context_prefixes")['admin']['prefix']]);
        }
        $panel .= "</nav>";
        return $panel;
    }

    var $header_template = "/parts/admin-header.html";
    var $footer_template = "/parts/admin-footer.html";

    // function generate_style_meta() {
    //     $link_tags = "";
    //     $compiled = "";
    //     $debug = app("debug");

    //     $default_settings = jsonc_decode(file_get_contents(__ENV_ROOT__ . "/config/setting_definitions.jsonc"),true);
    //     $app_packages = app('admin_css_packages');
    //     if(!$app_packages) $app_packages = [];

    //     $packages = array_merge($default_settings['css_packages']['default'],  $app_packages);

    //     foreach ($packages as $package) {
    //         $files = files_exist([
    //             __APP_ROOT__ . "/shared/css/$package",
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
    //     if ($link_tags === "") $link_tags = "<link rel=\"stylesheet\" href=\"/core-content/css/admin-package.css?{{app.version}}\">";

    //     if ($compiled !== "") {
    //         $minifier = new \MatthiasMullie\Minify\CSS();
    //         $minifier->add($compiled);
    //         $compiled = $minifier->minify();

    //         $cache = new CacheManager("css-precomp/admin-package.css");
    //         $cache->set($compiled, false);
    //     }
    //     return $link_tags;
    // }
}
