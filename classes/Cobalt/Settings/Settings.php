<?php

namespace Cobalt\Settings;

class Settings extends \Drivers\Database {

    const __SETTINGS__ = [
        __APP_ROOT__ . "private/config/settings.json",
        __APP_ROOT__ . "ignored/config/settings.json",
    ];

    public function get_collection_name() {
        return "cobalt_settings";
    }


    function __construct($bootstrap = false) {
        // Instance our parent class
        parent::__construct();

        $bootstrap_required = false;

        // Here we should decide which method we are going to take. Are we going
        // to use the existing database settings or do we need to bootstrap 
        // new settings?
        if ($bootstrap === false) {
            // Determine if we need to bootstrap
            $result = $this->get_settings_from_db();

            // If there are no updated settings in the database, then we need to
            // execute the bootstrap routine
            if ($result === null) $bootstrap_required = true;
            // Otherwise, we store the result of the lookup we just ran.
            else $this->settings = $result;
        }
        // We want to be able to programatically force the process
        else $bootstrap_required = true;

        if ($bootstrap_required) $this->settings = $this->bootstrap();
    }

    function get_max_m_time() {
        $max_m_time = 0;
        foreach ($this::__SETTINGS__ as $file) {
            $mtime = filemtime($file);
            if ($mtime === false) continue;
            if ($mtime > $max_m_time) $max_m_time = $mtime;
        }
        return $max_m_time;
    }

    function get_settings_from_db() {
        $max_m_time = $this->get_max_m_time();
        return $this->findOne(['compiled' => ['$gte' => $max_m_time]]);
    }

    /**
     * This is the function we use if we detect that the compiled
     * settings are outdated.
     * @return void 
     */
    function bootstrap() {
    }

    private $fields = [];
    private $awaiting_dependencies = [];

    function get_setting_classes() {
        $classes = scandir(__ENV_ROOT__ . "classes/Cobalt/Settings/Definitions/");
        // $classes += scandir(__APP_ROOT__ . "private/classes/Cobalt/Settings/Definitions/");
        
        foreach($classes as $c) {
            $class_name = substr($c,0,-4);
            $with_namespace = "\\Cobalt\\Settings\\Defintions\$class_name";
            $class = new $with_namespace();
            if(!empty($class->depends_on)) {
                $this->awaiting_dependencies[$class_name] = ['object' => $class];
            }
            $this->get_settings_value($class_name,$class);
        }

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
}
