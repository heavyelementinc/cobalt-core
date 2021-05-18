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
    private $active_file = __APP_ROOT__ . "/ignored/config/active_plugins.json";
    private $mod_config  = __APP_ROOT__ . "/ignored/config/plugins.json";
    private $plugin_config = __APP_ROOT__ . "/private/config/plugins.json";
    private $active = [];

    function __construct() {
        if (!file_exists($this->plugin_config)) $this->init_plugins_file();
        if (!file_exists($this->active_file)) $this->init_active_file();
        $this->active = get_json($this->active_file);
    }

    function init_plugins_file() {
        $contents = scandir(__PLG_ROOT__);
        if ($contents === false) return false;
        if (count($contents) === 2) return false;

        $directory = [];
        foreach ($contents as $plugin) {
            $json = "$plugin/config.json";
            if (!file_exists($json)) continue;
            array_push($plugin, get_json($json));
        }

        if (!file_put_contents($this->mod_config, json_encode($directory))) {
            throw new \Exception("Could not write plugin directory");
        }
    }

    function init_active_file() {
    }

    function get_active() {
        $plugins = [];
        foreach ($this->active as $i => $plg) {
            $name = $plg['name'];
            $entrypoint = __PLG_ROOT__ . "/$name/$name.php";
            $config = __PLG_ROOT__ . "/$name/config.json";
            if (!file_exists($entrypoint)) throw new MissingPlugin("Plugin $name is missing!");
            require_once $entrypoint;
            $instantiation = "\Plugins\\$name";
            $plugins[$i] = new $instantiation();
            $plugins[$i]->_config = get_json($config);
        }

        return $plugins;
    }
}
