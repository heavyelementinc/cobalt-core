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
    public string $userbar_admin_panel   = "<a href=\"".__APP_SETTINGS__['cobalt_base_path']."/\">".__APP_SETTINGS__['app_short_name']."</a>";

    function auth_panel() {
        if (!session_exists()) return "";
        
        // $session = session();

        $userPanel = "";
        // [
        //     'settings' => ($settings) ? "<option icon='cog' onclick=\"Cobalt.router.location = ''; return true;\">Settings</option>" : ""
        // ]

        $panel = "";
        if(__APP_SETTINGS__['manifest_engine'] === 1) $panel .= "<link rel='stylesheet' href='".to_base_url("/core-content/css/admin-panel.css")."?{{versionHash}}'>";

        $panel .= "<nav id='admin-panel'>$userPanel<ul class='admin-panel--nav-group directory--group'>";
    
    
        $panel .= get_route_group("admin_panel", [
            'prefix' => app("context_prefixes")['admin']['prefix'],
            'excludeWrapper' => true,
            'ulSuffix' => ["CoreAdmin@settings_index"],
        ]);
        // $settings = route("CoreAdmin@settings_index");
        // $panel .= ;
        $panel .= "</ul>";
        $panel .= "</nav>";
        return $panel;
    }

    var $header_template = "/parts/admin-header.html";
    var $footer_template = "/parts/admin-footer.html";
    var $meta_selector = "admin";

    // function generate_style_meta() {
    //     $link_tags = "";
    //     $compiled = "";
    //     foreach (array_merge(app('common-css-packages'), app('admin-css-packages')) as $package) {
    //         $files = files_exist([
    //             __APP_ROOT__ . "/shared/css/$package",
    //             __APP_ROOT__ . "/public/res/css/$package",
    //             __ENV_ROOT__ . "/shared/css/$package"
    //         ]);
    //         if ($debug === true) {
    //             $path = "/res/css/";
    //             if (strpos($files[0], "/shared/css/")) $path = "/core-content/css/";
    //             $link_tags .= "<link rel=\"stylesheet\" href=\"$path$package?{{versionHash}}\">";
    //         } else {
    //             $compiled .= "\n\n" . file_get_contents($files[0]);
    //         }
    //     }

    //     foreach ($GLOBALS['PACKAGES']['css'] as $public => $private) {
    //         $file = file_exists($private);
    //         if (!$file) continue;
    //         if ($debug === true) {
    //             $link_tags .= "<link rel=\"stylesheet\" href=\"$public?{{versionHash}}\">";
    //         } else {
    //             $compiled .= "\n\n" . file_get_contents($file);
    //         }
    //     }
    //     if ($link_tags === "") $link_tags = "<link rel=\"stylesheet\" href=\"/core-content/css/admin.css?{{versionHash}}\">";

    //     if ($compiled !== "") {
    //         $minifier = new \MatthiasMullie\Minify\CSS();
    //         $minifier->add($compiled);
    //         $compiled = $minifier->minify();

    //         $cache = new CacheManager("css-precomp/package.css");
    //         $cache->set($compiled, false);
    //     }
    //     return $link_tags;
    // }
    function getTheme() {
        if(__APP_SETTINGS__['universal_theme']) return parent::getTheme();
        return [
            "branding_increment"     => "0.05",
            "branding_rotation"      => "10",
            "color_branding"         => "#2F4858",
            "primary_increment"      => "0.1",
            "primary_rotation"       => "-10",
            "color_primary"          => "#009DDC",
            "neutral_increment"      => "0.1",
            "neutral_rotation"       => "0",
            "color_neutral"          => "#D2D6DA",
            "background_increment"   => "0.1",
            "background_rotation"    => "0",
            "color_background"       => "#F4F5F6",
            "issue_increment"        => "0.1",
            "issue_rotation"         => "-10",
            "color_issue"            => "#F96F5D",
            "color_font_body"        => "#02040F",
            "color_mixed_percentage" => 75,
        ];
    }
}
