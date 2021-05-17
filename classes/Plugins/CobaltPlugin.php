<?php

/**
 *  - Plugins IMPLEMENT the \Plugins\CobaltPlugin class
 *  - Plugins are self-contained in a single directory in __APP_ROOT__/plugins/<plugin name>
 *  - The plugin's entrypoint should be the name of the plugin class
 *  - All controllers, templates, js, settings, etc. are within that directory
 *  - No magic!
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Plugins;

interface CobaltPlugin {
    const __PLG_ROOT__ = __APP_ROOT__ . "/plugins";

    /** Plugin settings should be handled by SettingsManger */
    public function register_settings();

    /** If the plugin has its own permissions, we get them here */
    public function register_permissions();

    /** Plugin route registration should be handled by the router */
    public function register_routes($context);

    /** Register plugin's template directory
     * 
     * 
     */
    public function register_templates();
}
