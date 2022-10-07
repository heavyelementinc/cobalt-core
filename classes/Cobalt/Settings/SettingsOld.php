<?php

namespace Cobalt\Settings;

class Settings extends \Drivers\Database {

    const __SETTINGS__ = [
        __APP_ROOT__ . "private/config/settings.json",
        __APP_ROOT__ . "ignored/config/settings.json",
        __ENV_ROOT__ . "config/default_settings.json",
    ];

    public function get_collection_name() {
        return "cobalt_settings";
    }

    function __construct($bootstrap = false) {
        // Instance our parent class
        parent::__construct();

        $bootstrap_required = false;
        $this->settings = $this->fetch_latest_settings();

        if($bootstrap || !$this->settings || empty($this->settings)) $bootstrap_required = true;

        if ($bootstrap_required) $this->settings = $this->bootstrap();
    }

    function get_settings($bootstrap = false) {
        return $this->settings;
    }

    function get_max_m_time() {

    }

    function get_settings_from_db() {
        $max_m_time = $this->get_max_m_time();
        return $this->findOne(['compiled' => ['$gte' => $max_m_time]]);
    }

    /**
     * This is the method we call if we detect that the compiled
     * settings are outdated.
     * @return void 
     */
    private function bootstrap() {
        $dirs = [
            __ENV_ROOT__ . "/classes/Cobalt/Settings/Definitions/",
            __APP_ROOT__ . "/classes/Cobalt/Settings/Definitions/"
        ];

        $files = files_exist([
            __APP_ROOT__ . "/config/settings.json",
            __APP_ROOT__ . "/private/config/settings.json"
        ]);
        $settings = [];
        foreach($files as $file) {
            $contents = get_json($file);
            if(gettype($contents) !== "array") continue;
            $settings = array_merge($settings, $contents);
        }

        $this->default_settings = $settings;

        $classes = [];

        foreach($dirs as $dir) {
            $classes = [...$classes, ...$this->instance_classes($dir)];
        }

        return new \Cobalt\Settings\Manager($classes);
    }

    public function refresh_settings() {
        $this->settings = $this->bootstrap();
    }

    private $fields = [];
    private $awaiting_dependencies = [];

    function instance_classes($dir) {
        $classes = scandir($dir);
        $instances = [];
        
        foreach($classes as $c) {
            if($c[0] === "." || $c === "CobaltSetting.php") continue;
            $class_name = substr($c,0,-4);
            $with_namespace = "\\Cobalt\\Settings\\Definitions\\$class_name";
            // Instantiate the setting's class
            array_push($instances, new $with_namespace($this->settings[$class_name] ?? [], $this->settings));
        }

        return $instances;
    }

    function get_settings_value($name,$class){
        if(key_exists($name, $this->awaiting_dependencies)) {
            foreach($this->awaiting_dependencies[$name]->depends_on as $i => $dep) {
                // Remove the dependencies we're waiting on.
                if(key_exists($dep, $this->fields)) unset($this->awaiting_dependencies[$name]->depends_on[$i]);
            }
            if(!empty($this->awaiting_dependencies[$name]->depends_on)) return;
        }

        $interfaces = get_declared_interfaces();
        
    }

    function fetch_latest_settings() {
        $cache = iterator_to_array($this->find([], ['sort' => ['filemtime' => -1], 'limit' => 1]))[0];
        return ($cache) ? $cache : [];
    }
}
