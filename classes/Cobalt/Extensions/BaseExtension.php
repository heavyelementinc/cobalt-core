<?php
namespace Cobalt\Extensions;

use ArrayObject;
use MongoDB\BSON\Document;
use MongoDB\Model\BSONDocument;

abstract class BaseExtension {
    var $manifest;
    var $ready = false;
    var string $path = "";
    var string $cmd_dir = "cli_commands/";

    function __construct($manifest) {
        $this->manifest = $manifest;
        if(!$this->manifest['last_updated']) $this->manifest['last_updated'] = filemtime($this->manifest['install_path']) * 1000;
        $this->path = &$manifest->install_path;
        $this->ready = true;
    }

    abstract public function initialize($manifest):void;

    function register_cli_command_dir(&$paths) {
        $paths[] = "$this->path/$this->cmd_dir";
    }

    function register_cli_commands(&$commands) {
        $commands = array_merge($commands, hydrate_command_paths("$this->path/$this->cmd_dir"));
        // $scandir = scandir("$this->path/$this->cmd_dir");
        // if(!$scandir) return;
        // $paths = [];
        // foreach($scandir as $cmd) {
        //     if($cmd === "." || $cmd === "..") continue;
        //     $paths[] = "$this->path/$cmd";
        // }
        // $commands = array_merge($commands, $paths);
    }

    function register_templates_dir(&$paths) {
        $paths[] = "$this->path/templates/";
    }

    function register_classes_dir(&$paths) {
        $paths[] = "$this->path/classes/";
    }
    
    function register_controller_dir(&$controller_list) {
        $controller_list[] = "$this->path/controllers/";
    }

    function register_client_controllers(&$client_controllers) {
        $client_controllers[] = "$this->path/controllers/client/";
    }

    function register_routes($context, &$routes) {
        $route = "$this->path/routes/$context.php";
        if(file_exists($route)) $routes[] = $route;
    }

    function register_permissions(&$permissions) {
        $perms = $this->manifest->permissions;
        if($perms instanceof \MongoDB\Model\BSONArray) $perms = $perms->getArrayCopy();
        if($perms instanceof BSONDocument) $perms = $perms->getArrayCopy();
        array_merge($permissions, $perms ?? []);
    }

    function register_shared_dir(&$paths) {
        $paths[] = "$this->path/shared/";
    }

    function register_js_dirs(&$paths) {
        $paths[] = "$this->path/src/";
    }

    function register_settings_definitions(&$definitions, &$manifest) {
        if(count($this->manifest->settings)) $definitions[$this->path . '/manifest.json'] = doc_to_array($this->manifest['settings']);
        if($this->manifest->public) $manifest[] = $this->manifest->public;
        return;
        $m = [];

        foreach($this->manifest->public as $k => $v) {
            $m[$k] = $v;
            if($v instanceof ArrayObject) $m[$k] = $v->getArrayCopy();
            foreach($m[$k] as $k1 => $v1) {
                $m[$k][$k1] = $v1;
                if($v1 instanceof ArrayObject) $m[$k][$k1] = $v1->getArrayCopy();
            }
        }

        $manifest[] = $m;
    }

    function register_customizations(&$files) {
        $path = $this->path . "/config/customizations.php";
        if(!file_exists($path)) return;
        $files[] = $path;
    }

    // function register_settings(&$settings) {
    //     $set = $this->manifest->settings;
    //     if($set instanceof \MongoDB\Model\BSONArray) $set->getArrayCopy();
    // }

    /**
     * Modify the $session values you'd like to store as part of this session.
     * @param mixed $permission 
     * @return void
     */
    function session_creation(&$session) {}

    /**
     * Add additional fields to user accounts
     * @param array &$session 
     * @return void
     */
    function register_user_fields(array &$fields) {}

    
    function register_user_editor_tabs(array &$tabs) {}
}
