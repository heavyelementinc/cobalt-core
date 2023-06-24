<?php
namespace Cobalt\Extensions;

use ArrayObject;

abstract class BaseExtension {
    var $manifest;
    var $ready = false;
    var string $path = "";

    function __construct($manifest) {
        $this->manifest = $manifest;
        if(!$this->manifest['last_updated']) $this->manifest['last_updated'] = filemtime($this->manifest['install_path']) * 1000;
        $this->path = &$manifest->install_path;
        $this->ready = true;
    }

    function register_templates_dir(&$paths) {
        $paths[] = __APP_ROOT__ . "$this->path/templates/";
    }

    function register_classes_dir(&$paths) {
        $paths[] = __APP_ROOT__ . "$this->path/classes/";
    }
    
    function register_controller_dir(&$controller_list) {
        $controller_list[] = __APP_ROOT__ . "$this->path/controllers/";
    }

    function register_routes($context, &$routes) {
        $route = __APP_ROOT__ . "$this->path/routes/$context.php";
        if(file_exists($route)) $routes[] = $route;
    }

    function register_permissions(&$permissions) {
        $perms = $this->manifest->permissions;
        if($perms instanceof \MongoDB\Model\BSONArray) $perms->getArrayCopy();
        array_push($permissions, ... $perms ?? []);
    }

    function register_shared_dir(&$paths) {
        $paths[] = __APP_ROOT__ . "$this->path/shared/";
    }

    function register_settings_definitions(&$definitions, &$manifest) {
        if(count($this->manifest->settings)) $definitions[__APP_ROOT__ . $this->path . '/manifest.json'] = $this->manifest['settings'];
        if(!count($this->manifest->public)) return;
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


}
