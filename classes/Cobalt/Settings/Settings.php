<?php

/** # Cobalt Settings
 * To define a setting, it must have the following information:
 * 
 * "Example_setting": {
 *     "default": (some default value),
 *     "directives": { // Optional list of instructions to carry out on the option
 *        "
 *     },
 *     "
 * }
 * 
 * What is a setting?
 *   * Settings provide the application with basic configuration.
 *   * Settings control individual components of an app. Usually active/inactive or strings.
 *   * Settings are defined by Cobalt Engine or the app author.
 *   * Default settings may not overwrite changes users have made.
 * 
 * 
 * Settings storage:
 *   * Tier 1: Cached storage calculates a master list of settings <- Used most often
 *   * Tier 2: Settings that have been changed by the user
 *   * Tier 3: Settings defined in "__APP_ROOT__/config/custom_settings.json"
 *   * Tier 4: Settings defined in "__ENV_ROOT__/config/default_settings.json"
 */

namespace Cobalt\Settings;

use Exception;

class Settings extends \Drivers\Database {

    const __DEFINITIONS__ = [
        __ENV_ROOT__ . "/config/default_settings.jsonc",
        __APP_ROOT__ . "/config/settings.jsonc",
        __APP_ROOT__ . "/ignored/config/settings.jsonc",
    ];

    const __M_TIME_CANDIDATES__ = [
        __APP_ROOT__ . "routes/web.php",
        __APP_ROOT__ . "routes/apiv1.php",
        __APP_ROOT__ . "routes/admin.php",
        __APP_ROOT__ . "routes/webhook.php",
        __ENV_ROOT__ . "routes/web.php",
        __ENV_ROOT__ . "routes/apiv1.php",
        __ENV_ROOT__ . "routes/admin.php",
        __ENV_ROOT__ . "routes/webhook.php",
    ];

    // const __SETTINGS__ = [
    //     __ENV_ROOT__ . "/config/flags.jsonc",
    //     __APP_ROOT__ . "/ignored/config/custom_settings.json",
    //     __APP_ROOT__ . "/ignored/config/custom_settings.jsonc",
    // ];

    function __construct($bootstrap = false) {
        // Instance our parent class
        parent::__construct();

        $bootstrap_required = false;
        $this->max_m_time = $this->getMaxMTime();
        $this->__settings = $this->fetchCachedSettings();
        $bootstrap_required = $this->isBootstrapRequired($bootstrap);
        if ($bootstrap_required) $this->bootstrap();
    }

    function get_settings() {
        return doc_to_array($this->__settings);
    }

    function get_collection_name() {
        return "CobaltSettings";
    }

    final public function isBootstrapRequired($bootstrap = false) {
        // If we're forced to do a bootstrap, do it.
        if($bootstrap) return true;
        // If there are no settings, do a bootstrap
        if(!$this->__settings) return true;
        // If settings are an empty array, do a bootstrap
        if(empty($this->__settings)) return true;
        // If there is no "Meta->max_m_time" property, do a bootstrap
        if(property_exists($this->__settings, "Meta") && !property_exists($this->__settings->Meta, "max_m_time")) return true;
        // If the cachedk max_m_time is less than the current max_m_time, do a bootstrap
        if($this->Meta->max_m_time < $this->max_m_time) return true;
        // Otherwise, we don't need to bootstrap.
        return false;
    }

    final public function bootstrap() {
        // Get settings definitions from __DEFINITION__ files
        $this->getSettingDefinitions();
        $json = $this->definitions;

        $this->__user_modified_settings = $this->fetchModifiedSettings();

        // Process each setting and get the value
        $toCache = [];
        foreach($json as $name => $definition) {
            $setting = false;
            if(key_exists("definition",$definition)) {
                $def = "\\Cobalt\\Settings\Definitions\\$definition[definition]";
                try{
                    $setting = new $def($name, $this->normalizeSetting($name, $definition), $this->__user_modified_settings, $this->__settings);
                } catch (\Exception $e) {
                    die("Setting `$name` specifies a bad definition");
                }
                if($setting instanceof CobaltSetting === false) $setting = false;
            }
            if(!$setting) $setting = new CobaltSetting($name, $this->normalizeSetting($name, $definition), $this->__user_modified_settings, $this->__settings);

            $toCache[$name] = $setting->get_value();
        }

        // Get the ID of the cached settings
        $id = $this->__settings->_id;
        if(!$id) $this->__id();
        $this->updateOne(['_id' => $id],
        [
            '$set' => array_merge(
                $toCache,
                [
                    'Meta.type' => 'cache',
                    'Meta.max_m_time' => $this->max_m_time
                ]
            )
        ],
        ['upsert' => true]);
        $this->__settings = $this->fetchCachedSettings();

        $this->bootstrapManifestData();
        
    }

    private function normalizeSetting($name, $data) {
        if(gettype($data) !== "array") $data = ['default' => $data, 'shorthand' => true];
        return array_merge([
            'default' => null,
            'directives' => [],
            'meta' => [
                'editable' => false
            ],
            'validate' => []
        ], $data);
    }

    private function getMaxMTime() {
        $max_m_time = 0;
        foreach ($this::__DEFINITIONS__ as $file) {
            $mtime = filemtime($file);
            if ($mtime === false) continue;
            if ($mtime > $max_m_time) $max_m_time = $mtime;
        }
        return $max_m_time;
    }

    public function getSettingDefinitions(){
        try {
            $raw_decode = [];
            foreach($this::__DEFINITIONS__ as $file) {
                if(!file_exists($file)) continue;
                $raw_decode[$file] = jsonc_decode(file_get_contents($file), true, 512, JSON_ERROR_SYNTAX);
            }

            $values = [];
            $definitions = [];
            foreach($raw_decode as $filename => $settings) {
                $this->parseSetting($values, $definitions, $settings, $filename);
            }
            // $json = get_all_where_available($this::__DEFINITIONS__, true, true);
        } catch (\Exception $e) {
            die($e);
        } 
        $this->default_values = $values;
        $this->definitions    = $definitions;
    }

    private function parseSetting(&$values, &$def, $settings, $filename) {
        $detect_definition = ['default','directives','meta'];
        foreach($settings as $name => $data) {
            $isDefinition = false;
            if(gettype($data) == "array") {
                $isDefinition = true;
                if(array_intersect(array_keys($data), $detect_definition)) {
                    $data['defined'] = $filename;
                    $data['shorthand'] = false;
                    $def[$name] = array_merge($def[$name] ?? [], $data);
                }
                $values[$name] = $data['default'] ?? $data['directives']['merge'] ?? $data['directives']['mergeAll'] ?? null;
            }
            if(!$isDefinition) {
                $values[$name] = $data;
                if(!isset($def[$name])) $def[$name] = ['default' => $data, 'shorthand' => true];
                else $def[$name]['default'] = $data;
            }
        }
    }
        
    public function fetchCachedSettings() {
        $cursor = $this->find([
            'Meta.type' => 'cache'
        ],
        [
            'sort' => ['Meta.max_m_time' => -1],
            'limit' => 1
        ]);
        $array = iterator_to_array($cursor);
        if($array[0]) return $array[0];
        return [];
    }
    
    public function fetchModifiedSettings() {
        $cursor = $this->find([
            'Meta.type' => 'modified'
        ],
        [
            'sort' => ['Meta.max_m_time' => -1],
            'limit' => 1
        ]);
        $array = iterator_to_array($cursor);
        if($array[0]) return iterator_to_array($array[0]);
        return [];
    }


    public function bootstrapManifestData() {

    }
}


    // function get_definititions() {
    //     $detect_definition = ['default','directives','meta'];
    //     $setting_values = [];
    //     $definitions = [];
    //     foreach($this::__DEFINITIONS__ as $index => $file) {
    //         if(!file_exists($file)) continue;
    //         try {
    //             $definitions[$file] = jsonc_decode($file,true, JSON_ERROR_SYNTAX);
    //         } catch (\Exception $e) {
    //             die("Syntax error in `" . str_replace([__APP_ROOT__, __ENV_ROOT__],[""],$file) . '`');
    //         }
    //         foreach($definitions[$file] as $name => $setting) {
    //             if(gettype($setting) === "array") 
    //         }
    //     }
    //     $json = array_merge(...$definitions);

    //     return [$json, $definitions];
    // }