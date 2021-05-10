<?php

namespace Web;
class AdminHandler extends WebHandler{
    var $route_table_cache = "js-precomp/admin-router-table.js";
    // var $script_cache_name = "template-precomp/admin-script.html";
    // function post_router_init(){
    //     $this->prepare_html_framework();
    // }
    function auth_panel(){
        if(!session_exists()) return "";
        $panel = "<link rel='stylesheet' href='/core-content/css/admin-panel.css?{{app.version}}'>";
        $panel .= "<nav id='admin-panel'><ul>";
        $admin_prefix = app("context_prefixes")['admin']['prefix'];
        foreach($GLOBALS[$GLOBALS['route_table_address']]['get'] as $route){
            if($route['panel_name'] === null) continue;
            $path = substr($route['original_path'],1);
            $panel .= "<li><a href='$admin_prefix$path'>$route[panel_name]</a></li>";
        }
        $panel .= "</ul></nav>";
        return $panel;
    }
}