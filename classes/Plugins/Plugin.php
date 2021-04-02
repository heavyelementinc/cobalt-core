<?php
/**
 * GOALS FOR PLUGINS:
 *  - Plugins should EXTEND the \Plugins\Plugin class
 *  - Plugins should be self-contained in a single directory in __APP_ROOT__/plugins/<plugin name> (__PLG_ROOT__)
 *  - All controllers, templates, js, settings, etc. should be within that directory
 *  - 
 */
namespace Plugins;
class Plugin{
    const __PLG_ROOT__ = __APP_ROOT__ . "/plugins";
    function __construct(){

    }

    function register_settings(){
        /** Plugin settings should be handled by SettingsManger */
    }

    function register_permissions(){
        /** If the plugin has its own permissions, we get them here */
    }

    function register_routes(){
        /** Plugin route registration should be handled by the router */
    }

    function get_admin_panel_components(){
        /** Admin panel components should return an array,
         * 
         *  0 => ['panel_nav_link' => [...]]
         *  1 => ['panel_controller' => [...]]
         * 
         */
    }

    
}