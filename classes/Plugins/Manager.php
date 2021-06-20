<?php

/**
 * Plugin Manager
 * 
 * This class manages active plugins
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Plugins;

use \Plugins\Exceptions\MissingPlugin;

class Manager {

    /** $plugins_enabled contains a JSON representation of all active plugin 
     * configuration files */
    private $plugin_directory = __APP_ROOT__ . "/ignored/config/plugin_directory.json";
    private $enabled_plugins = __APP_ROOT__ . "/ignored/config/plugin_enabled.json";
    /** $plugins_available defines the plugins that are required for the app to
     * work.
     */
    private $plugins_available = __APP_ROOT__ . "/private/config/plugins_available.json";

    /** List of active plugins */
    private $active = [];
    private $enabled_names = [];
    private $directory = [];

    function __construct() {
        // if (!file_exists($this->plugins_available)) $this->init_available_file();
        // if ($GLOBALS['time_to_update']) 
        $this->update_plugin_database();
        $this->directory = get_json($this->plugin_directory) ?? [];
        $this->enabled_names = array_unique(get_json($this->enabled_plugins));
        $this->active = $this->get_enabled_plugins();
    }

    /* PUBLIC FUNCTIONS */

    public function get_plugin_by_name($name) {
        if (!isset($this->directory[$name])) return null;
        return $this->directory[$name];
    }

    public function change_plugin_state($name, $state) {

        if (!key_exists($name, $this->directory)) throw new MissingPlugin("That plugin doesn't exist.");
        if ($state) $this->enabled_names = array_unique([...$this->enabled_names, $name]);
        else {
            $index = array_search($name, $this->enabled_names);
            if ($index === false) throw new MissingPlugin("That plugin isn't enabled yet");
            unset($this->enabled_names[$index]);
        }
        $this->write_enabled_directory($this->enabled_names);
    }

    public function instantiate_active_plugins() {
        $plugins = [];
        foreach ($this->active as $i => $plg) {
            $name = $plg['plugin'];
            $root = __PLG_ROOT__ . "/$name/";
            $entrypoint = $root . "$name.php";
            if (!file_exists($entrypoint)) throw new MissingPlugin("Plugin $name is missing!");
            require_once $entrypoint;
            $instantiation = "\Plugins\\$name";
            $plugins[$i] = new $instantiation($plg);
            $plugins[$i]->_config = $plg;
            $plugins[$i]->__PLUGIN_ROOT__ = $root;
        }

        return $plugins;
    }

    public function get_plugin_list($url) {
        $all_plugins = $this->rebuild_database();
        $enabled_column_size = ".05";
        $name_column_size = ".3";
        $number_column_size = ".02;justify-content:center";
        $content = "<flex-table><flex-row>
        <flex-header style='flex-grow:$enabled_column_size'>Enabled</flex-header>
        <flex-header style='flex-grow:$name_column_size'>Plugin Name</flex-header>
        <flex-header>Description</flex-header>
        <flex-header style='flex-grow:$number_column_size'>Settings</flex-header>
        <flex-header style='flex-grow:$number_column_size'>Permissions</flex-header>
        </flex-row>";
        foreach ($all_plugins as $plugin) {
            $enabled = (in_array($plugin['plugin'], $this->enabled_names)) ? "true" : "false";
            $content .= "<flex-row>";
            $content .= "<flex-cell style='flex-grow:$enabled_column_size;justify-content:center;'><form-request method=\"POST\" action=\"/api/v1/plugin/enable/$plugin[plugin]\" autosave=\"true\">
            <input-switch name=\"enabled\" checked=\"$enabled\" small></input-switch>
        </form-request></flex-cell>";
            $content .= "<flex-cell style='flex-grow:$name_column_size'><a href='$url" . "$plugin[plugin]'>$plugin[name]</a></flex-cell>";
            $content .= "<flex-cell>$plugin[description]</flex-cell>";
            $content .= "<flex-cell style='flex-grow:$number_column_size'>" . count($plugin['settings']) . "</flex-cell>";
            $content .= "<flex-cell style='flex-grow:$number_column_size'>" . count($plugin['permissions']) . "</flex-cell>";
            $content .= "</flex-row>";
        }
        $content .= "</flex-table>";
        return $content;
    }

    /* PRIVATE FUNCTIONS */

    private function init_available_file() {
        if (!is_dir(__PLG_ROOT__)) {
            mkdir(__PLG_ROOT__);
        }
        $contents = scandir(__PLG_ROOT__);
        if ($contents === false) return false;
        if (count($contents) === 2) return false;

        if (!file_put_contents($this->plugins_available, json_encode($this->rebuild_database()))) {
            throw new \Exception("Could not write plugin directory");
        }
    }

    private function update_plugin_database() {
        $data = $this->rebuild_database();
        $result = [];
        foreach ($data as $plugin) {
            $result[$plugin['plugin']] = $plugin;
        }
        $this->write_directory($result);
        return $result;
    }

    // function init_enabled_file() {
    //     $dir = pathinfo($this->plugin_directory, PATHINFO_DIRNAME);
    //     if (!is_dir($dir)) mkdir($dir);

    //     if (!file_put_contents($this->plugin_directory, json_encode($this->rebuild_database()))) {
    //         throw new \Exception("Cannot write plugins enabled file.");
    //     }
    // }

    private function rebuild_database() {
        $database = [];
        $contents = scandir(__PLG_ROOT__);
        foreach ($contents as $plugin) {
            $json = __PLG_ROOT__ . "/$plugin/config.json";
            if (!file_exists($json)) continue;
            array_push($database, get_json($json));
        }

        return $database;
    }

    private function get_enabled_plugins() {
        $enabled = get_json($this->enabled_plugins) ?? [];
        $result = [];
        foreach ($enabled as $item) {
            if (isset($this->directory[$item])) $result[$item] = $this->directory[$item];
            // else throw new MissingPlugin("Cannot find active plugin: $item");
        }
        return $result;
    }

    private function write_directory($data) {
        if (!file_put_contents($this->plugin_directory, json_encode($data)))
            throw new \Exception("Cannot write plugin directory");
    }

    private function write_enabled_directory($data) {
        if (!file_put_contents($this->enabled_plugins, json_encode($data)))
            throw new \Exception("Cannot write enabled plugin file");
    }
}
