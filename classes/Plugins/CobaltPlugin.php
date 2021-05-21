<?php

/**
 *  - Plugins EXTEND the \Plugins\CobaltPlugin class
 *  - Plugins are self-contained in a single directory in __APP_ROOT__/plugins/<plugin name>
 *  - The plugin's entrypoint should be the name of the plugin class which is 
 *      the same as the plugin directory
 *  - All controllers, templates, js, settings, etc. are within that directory
 *  - No magic!
 *
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Plugins;

class CobaltPlugin {

    /** Returns an array of Cobalt settings definitions
     * @return array Cobalt settings definitions */
    public function register_settings() {
        return $this->_config['settings'] ?? [];
    }

    /** Returns an array of Cobalt permissions
     * @return array Cobalt permissions
     * @todo implement this
     */
    public function register_permissions() {
        return $this->_config['permissions'] ?? [];
    }

    /** Returns the current context's routes.
     * @param string $context the current route context
     * @return string "/routes/$context.php"
     */
    public function register_routes($context) {
        $dir = $this->get_dir('routes_dir', "/routes");
        if ($dir === null) return null;
        return "$dir/$context.php";
    }

    /** @return string this plugins CONTROLLER directory */
    public function register_controllers() {
        return $this->get_dir("controllers_dir", "/controllers/");
    }

    /** @return string this plugins TEMPLATE directory
     * @todo implement this
     */
    public function register_templates() {
        return $this->get_dir("template_dir", "/templates/");
    }

    /** @return string this plugins SHARED CONTENT directory 
     * @todo implement this
     */
    public function register_shared_content_dir() {
        return $this->get_dir("shared_dir", "/shared/");
    }

    /** @return string this plugins CLI COMMANDS directory 
     * @todo implement this
     */
    public function register_cli_commands() {
        return $this->get_dir('cli_dir', "/cli/commands/");
    }

    /** Handles manually loading this plugins dependencies.
     * @return void 
     * @todo implement this
     * */
    public function register_dependencies() {
    }

    /** @return string this plugins JAVASCRIPT directory
     * @todo implement this
     */
    public function register_javascript_dir() {
        return $this->get_dir('js_dir', "/js/");
    }

    /** @return string this plugins CSS directory
     * @todo implement this
     */
    public function register_css_dir() {
        return $this->get_dir('css_dir', "/shared/css/");
    }

    /** @return string validated directory relative to this plugin's path */
    private function get_dir($key, $default) {
        $dir = $this->_config[$key] ?? $default;
        if (!is_dir($this->__PLUGIN_ROOT__ . $dir)) return null;
        return $this->__PLUGIN_ROOT__ . $dir;
    }

    /** Use add_vars() to register variables for every web context route
     * @return void use \add_vars($vars);
     * @todo implement this
     */
    public function register_web_variables() {
    }

    /** Use add_vars() to register variables fro every admin context route
     * @return void use \add_vars($vars);
     * @todo implement this
     */
    public function register_admin_variables() {
    }
}
