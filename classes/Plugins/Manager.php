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
    private $plugins_enabled = __APP_ROOT__ . "/ignored/config/plugins_enabled.json";

    /** $plugins_available defines the plugins that are required for the app to
     * work.
     */
    private $plugins_available = __APP_ROOT__ . "/private/config/plugins_available.json";

    /** List of active plugins */
    private $active = [];

    function __construct() {
        if (!file_exists($this->plugins_available)) $this->init_available_file();
        if (!file_exists($this->plugins_enabled)) $this->init_enabled_file();
        $this->active = get_json($this->plugins_enabled) ?? [];
    }

    function init_available_file() {
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

    function init_enabled_file() {
        $dir = pathinfo($this->plugins_enabled, PATHINFO_DIRNAME);
        if (!is_dir($dir)) mkdir($dir);
        if (!file_put_contents($this->plugins_enabled, json_encode($this->rebuild_database()))) {
            throw new \Exception("Cannot write plugins enabled file.");
        }
    }

    function rebuild_database() {
        $database = [];
        $contents = scandir(__PLG_ROOT__);
        foreach ($contents as $plugin) {
            $json = __PLG_ROOT__ . "/$plugin/config.json";
            if (!file_exists($json)) continue;
            array_push($database, get_json($json));
        }

        return $database;
    }

    function get_active() {
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
}
