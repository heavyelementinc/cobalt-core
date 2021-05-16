<?php

/**
 * GOALS FOR PLUGINS:
 *  - Plugins should IMPLEMENT the \Plugins\Plugin class
 *  - Plugins should be self-contained in a single directory in __APP_ROOT__/plugins/<plugin name> (__PLG_ROOT__)
 *  - All controllers, templates, js, settings, etc. should be within that directory
 *  - 
 */

namespace Plugins;

interface CobaltPlugin {
    const __PLG_ROOT__ = __APP_ROOT__ . "/plugins";

    /** Plugin settings should be handled by SettingsManger */
    public function register_settings();

    /** If the plugin has its own permissions, we get them here */
    public function register_permissions();

    /** Plugin route registration should be handled by the router */
    public function register_routes();

    /** Register plugin's template directory */
    public function register_templates();
}
