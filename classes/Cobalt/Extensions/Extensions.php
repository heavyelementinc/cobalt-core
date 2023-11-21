<?php

namespace Cobalt\Extensions;

use Cobalt\Extensions\Exceptions\ExtensionNotReady;
use Cobalt\Extensions\Exceptions\ManifestException;
use Exception;
use MongoDB\BSON\ObjectId;

class Extensions extends \Drivers\Database {
    var bool $safe_mode = false;
    var $options = [];
    var $options_id = null;
    var array $directories = [
        __APP_ROOT__ . "/extensions/",
        __ENV_ROOT__ . "/extensions/",
    ];
    var array $initialized_extensions = [];

    function __construct($safe_mode = false, $database = null, $collection = null) {
        parent::__construct($database, $collection);
        $this->safe_mode = $safe_mode;
        $this->options = $this->findOne(['is_options' => true]);
        if($this->options) $this->options_id = $this->options->_id;
        else $this->options_id = new ObjectId();
    }

    public function get_collection_name() {
        return "Extensions";
    }

    public function build_extension_list() {
        $registered = $this->find(['is_extension' => true]);
        foreach($registered as $current) {
            if(!is_dir($current->install_path)) {
                $this->unregister_extension($current->_id);
                continue;
            }

            $sanitized = $this->sanitize_install_path($current->install_path,"","");
            // if(!in_array($sanitized, $this->directories)) $this->unregister_extension($current->_id);
        }

        $ext_list = [];
        foreach($this->directories as $dir) {
            if(!is_dir($dir)) continue;
            foreach(\scandir($dir) as $d) {
                if($d[0] === ".") continue;
                $ext_list[] = $dir . $d;
            }
        }

        $extensions_found = 0;

        foreach($ext_list as $ext_dir) {
            // $ext_path = str_replace("//", "/","$dir/$ext_dir");
            $ext_path = $ext_dir;
            if(!is_dir($ext_path)) throw new Exception("Could not find extension directory");
            $man_path = $ext_path . "/manifest.json";
            if(!file_exists($man_path)) throw new ExtensionNotReady("Extension $ext_dir is missing a manifest");
            $manifest = get_json($man_path);
            $this->manifest_sanity_check($manifest, $ext_path);
            $this->register_extension($manifest);
        }

        $this->set_last_rebuild_date(time() * 1000);
    }

    /**
     * Required manifest keys are:
     *   * `class`         - The class 
     *   * `version`       - The version number of the extension
     *   * `extension_api` - The minimum compatible extension version number
     *   * `reposiory`     - A URL to the git repository for this extension
     *   * `update_url`    - The URL to the git repo for updates, inherits from 'repository' if blank
     *   * `meta`          - An object containing name, description, and icon
     *   * `meta.author`   - The name of the author of this extension
     *   * `meta.name`     - The name of
     *   * `grants`        - Object of objects {"method_name": {"required": true}}
     * @param mixed $manifest 
     * @param mixed $ext_dir 
     * @return void 
     * @throws ManifestException 
     */

    private function manifest_sanity_check(&$manifest, $ext_path){
        // We don't want to allow manifests to activate extensions
        unset($manifest['active']);

        // Natural data
        $manifest['install_path'] = str_replace(["//"], ["/"], $ext_path);
        $debug_path = $this->sanitize_install_path($manifest['install_path']);
        $manifest['is_extension'] = true;

        if(!file_exists($ext_path . "/" . $manifest['entrypoint'])) throw new Exceptions\ManifestException("The specified entrypoint cannot be found.");

        if(!key_exists('class', $manifest)) throw new Exceptions\ManifestException("$debug_path No class name specified. The `class` field should match the class name defined in `entrypoint`.");
        if(!key_exists('uuid', $manifest)) throw new Exceptions\ManifestException("$debug_path No uuid found. The `uuid` should differentiate extension instances.");
        if(!key_exists("version", $manifest)) throw new Exceptions\ManifestException("$debug_path No version found. The `version` field should define the current version of this extension.");
        if(!key_exists("extension_api", $manifest)) throw new Exceptions\ManifestException("$debug_path No extension_api found. The `extension_api` should define the minimum Cobalt Engine Extension API this extension relies on.");
        
        if(!key_exists("repository", $manifest)) throw new Exceptions\ManifestException("$debug_path No repository found. The `repository` should define a URL pointing to a valid Git repo.");
        if(!key_exists("update_url", $manifest)) $manifest['update_url'] = $manifest['repository'];

        // Meta validation
        if(!key_exists("meta", $manifest)) $manifest['meta'] = [];
        if(!key_exists("name", $manifest['meta'] ?? [])) $manifest['meta']['name'] = $manifest['class'];
        if(!key_exists("description", $manifest['meta'])) $manifest['meta']['description'] = "No description";
        if(!key_exists("icon", $manifest['meta'])) $manifest['meta']['icon'] = ["type" => "mdi", "value" => "puzzle"];
        if(!key_exists("author", $manifest['meta'])) $manifest['meta']['author'] = $manifest['repository'];

    }

    public function sanitize_install_path($path, $app = "[app]", $core = '[core]') {
        return str_replace([__APP_ROOT__, __ENV_ROOT__, "//"], [$app, $core, "/"], $path);
    }

    private function register_extension($manifest) {
        return $this->updateOne(
            ['uuid' => $manifest['uuid']],
            ['$set' => $manifest],
            ['upsert' => true]
        );
    }

    private function unregister_extension($id) {
        return $this->deleteOne(['_id' => $id]);
    }

    public function initialize_active_extensions() {
        if($this->safe_mode) return;
        if($this->extension_cache_rebuild_required()) $this->build_extension_list();

        $active = $this->find(['active' => true]);

        foreach($active as $manifest) {
            $this->initialize_extension($manifest);
        }
    }

    private function extension_cache_rebuild_required() {
        $rebuild = $this->options->last_rebuild;
        if($rebuild == null) return true;
        $last = $this->options->last_rebuild->toDateTime()->getTimestamp();
        foreach($this->directories as $dir) {
            if(!file_exists($dir)) continue;
            $mtime = filemtime($dir);
            if(!$mtime) continue;
            if($last > $mtime) return true;
        }
        return false;
    }

    private function set_last_rebuild_date($time = null) {
        if($time == null) $time = time() * 1000;
        $this->set_option('last_rebuild', new \MongoDB\BSON\UTCDateTime($time));
    }

    public function set_option($option_name, $value) {
        $result = $this->updateOne(
            [
                '_id' => $this->options_id,
                'is_options' => true,
            ],
            ['$set' => [$option_name => $value]],
            ['upsert' => true]
        );
    }

    public function initialize_extension($manifest) {
        require_once "$manifest[install_path]/$manifest[entrypoint]";
        $extension_literal = "\\Cobalt\\Extensions\\$manifest[class]";
        $count = count($this->initialized_extensions);
        $this->initialized_extensions[] = new $extension_literal($manifest);
        $this->initialized_extensions[$count]->initialize($manifest);
    }

    public function get_grants($extension) {
        $html = "";
        foreach($extension['grants'] as $method => $meta) {
            $required = "required--" . json_encode($meta['required'] ?? false);
            $checked = ($this->has_grant($extension, $method)) ? "check-circle-outline" : "shield-outline";
            $m = $this->valid_grants[$method];
            $html .= "<li class='$required'><i class='state' name='$checked'></i> <label> {$m['name']}</label></li>";
        }
        return view("/cobalt/extensions/security-grants.html",['html' => $html, 'ext' => $extension]);
    }

    public function has_grant($ext, $method) {
        if(!key_exists($method, $this->valid_grants)) throw new Exception("That grant does not exist");
        return ($ext->grants->{$method} ?? $this->valid_grants[$method]['default']) ? true : false;
    }

    public $valid_grants = [
        'register_settings_definition' => [
            'name' => "Register extension's settings with application",
            'icon' => 'knob',
            'default' => true
        ],
        'register_routes' => [
            'name' => "Register extension routes",
            'icon' => 'router',
            'default' => true
        ],
        'register_controller_dir' => [
            'name' => "Define route controllers",
            'icon' => 'application-braces',
            'default' => true
        ],
        'register_classes_dir' => [
            'name' => "Register classes",
            'icon' => 'code-braces',
            'default' => true
        ],
        'register_shared_dir' => [
            'name' => "Register shared content (CSS, other assets)",
            'icon' => 'border-style',
            'default' => true
        ],
        'register_js_dirs' => [
            'name' => "Register JavaScript files",
            'icon' => 'code-json',
            'default' => true
        ],
        'register_templates_dir' => [
            'name' => "Register template directory",
            'icon' => 'application-brackets-outline',
            'default' => true
        ],
        
        'register_permissions' => [
            'name' => "Define extension-specific permissions",
            'icon' => 'shield-key',
            'default' => true
        ],
        'login_process' => [
            'name' => "Intercept the login process",
            'icon' => 'key-chain',
            'default' => false
        ],
        "session_creation" => [
            'name' => "Modify user session login data before storage",
            'icon' => 'login',
            'default' => false
        ]
    ];

    /**
     * Calling methods from an extension is easy:
     * 
     * 
     * @return void 
     */
    static function invoke($method_name, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null) {
        global $EXTENSION_MANAGER;
        if(!$EXTENSION_MANAGER) return;

        if(!$method_name) new \Exception("No method name provided.");
        
        
        foreach($EXTENSION_MANAGER->initialized_extensions as $ext) {
            if(!method_exists($ext, $method_name)) throw new Exception("There is no '$method_name' extension endpoint.");
            if(!$ext->ready) throw new Exceptions\ExtensionNotReady($ext::class . " is not ready. This is probably because the extension has a constructor that fails to explicitly call its parent constructor");
            $ext->{$method_name}($arg1, $arg2, $arg3, $arg4, $arg5);
        }
    }

    static function get_active_count() {
        global $EXTENSION_MANAGER;
        return count($EXTENSION_MANAGER->initialized_extensions);
    }
    
    static function get_total_count() {
        global $EXTENSION_MANAGER;
        return $EXTENSION_MANAGER->count(['is_extension' => true]);
    }

}
