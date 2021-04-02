<?php

namespace Web;
class AdminHandler extends WebHandler{
    
    // function post_router_init(){
    //     $this->prepare_html_framework();
    // }
    function auth_panel(){
        $panel = "<link rel='stylesheet' href='/core-content/css/admin-panel.css?{{app.version}}'>";
        $panel .= "<nav id='admin-panel'><ul>";
        $admin_prefix = app("api_routes")['admin']['prefix'];
        foreach($GLOBALS[$GLOBALS['route_table_address']]['get'] as $route){
            if($route['panel_name'] === null) continue;
            $path = substr($route['original_path'],1);
            $panel .= "<li><a href='$admin_prefix$path'>$route[panel_name]</a></li>";
        }
        $panel .= "</ul></nav>";
        return $panel;
    }
}