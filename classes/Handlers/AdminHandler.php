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
        
        // $session = session();
        $settings = route("CoreAdmin@settings_index");
        $customize = route("Customizations@index");
        $userPanel = view('/admin/users/session-panel.html',[]);
        // [
        //     'settings' => ($settings) ? "<option icon='cog' onclick=\"Cobalt.router.location = ''; return true;\">Settings</option>" : ""
        // ]

        $panel = "<link rel='stylesheet' href='/core-content/css/admin-panel.css?{{versionHash}}'>";

        $panel .= "<nav id='admin-panel'>{{!admin_masthead}}$userPanel<ul class='admin-panel--nav-group directory--group'>";
    
    
        $panel .= get_route_group("admin_panel", [
            'prefix' => app("context_prefixes")['admin']['prefix'],
            'excludeWrapper' => true,
            'ulSuffix' => ["CoreAdmin@settings_index"],
        ]);
        // $settings = route("CoreAdmin@settings_index");
        // $panel .= ;
        $panel .= "</ul>";
        $panel .= "<ul class='settings-panel--footer'>";
        $panel .= (__APP_SETTINGS__['Notifications_system_enabled']) ? "<notify-button></notify-button>" : "";
        $panel .= ($customize) ? "<a class='admin-panel--customize-link' href='$customize' rel='Customize Panel' title='Customize Panel'><i name='application-edit-outline'></i><span class='contextual contextual--hover'>Customize</span></a>" : "";
        $panel .= ($settings) ? "<a class='admin-panel--settings-link' href='$settings' rel='Settings Panel' title='Settings Panel'><i name='cog'></i><span class='contextual contextual--hover'>Settings</span></a>" : "";
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

}
