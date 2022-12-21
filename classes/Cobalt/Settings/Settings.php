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
use Cobalt\Settings\Exceptions\AliasMissingDependency;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

class Settings extends \Drivers\Database {

    const __DEFINITIONS__ = [
        __ENV_ROOT__ . "/config/default_settings.jsonc",
        __APP_ROOT__ . "/config/settings.jsonc",
        __APP_ROOT__ . "/ignored/config/settings.jsonc",
        __APP_ROOT__ . "/config/settings.json",
        __APP_ROOT__ . "/ignored/config/settings.json",
    ];

    const __MANIFESTS__ = [
        __ENV_ROOT__ . "/manifest.jsonc",
        __APP_ROOT__ . "/manifest.jsonc",
        __APP_ROOT__ . "/manifest.json",
    ];

    var $mtime_candidates = [
        
    ];

    // const __SETTINGS__ = [
    //     __ENV_ROOT__ . "/config/flags.jsonc",
    //     __APP_ROOT__ . "/ignored/config/custom_settings.json",
    //     __APP_ROOT__ . "/ignored/config/custom_settings.jsonc",
    // ];

    function __construct($bootstrap = true) {
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
        if($this->__settings->Meta->max_m_time < $this->max_m_time) return true;
        // Otherwise, we don't need to bootstrap.
        return false;
    }

    final public function bootstrap() {
        // Get settings definitions from __DEFINITION__ files
        $this->getSettingDefinitions();
        $json = $this->definitions;

        $this->__user_modified_settings = $this->fetchModifiedSettings();

        $this->instances = [];
        $this->waitingForDependencies = [];

        // Process each setting and get the value
        $toCache = [];
        foreach($json as $name => $definition) {
            $setting = false;
            if(key_exists("definition",$definition)) {
                $def = "\\Cobalt\\Settings\Definitions\\$definition[definition]";
                try{
                    $setting = new $def($name, $this->normalizeSetting($name, $definition), $this->__user_modified_settings, $this->__settings, $this, $toCache);
                } catch (\Exception $e) {
                    die("Setting `$name` specifies a bad definition");
                }
                if($setting instanceof CobaltSetting === false) $setting = false;
            }
            if(!$setting) $setting = new CobaltSetting($name, $this->normalizeSetting($name, $definition), $this->__user_modified_settings, $this->__settings, $this, $toCache);

            $this->instances[$name] = $setting;
            try{
                $toCache[$name] = $setting->get_value();
            } catch (AliasMissingDependency $e) {
                $this->waitingForDependencies[$name] = $setting;
            }
        }

        foreach($this->waitingForDependencies as $name => $setting) {
            try{
                $toCache[$name] = $setting->get_value();
            } catch (AliasMissingDependency $e) {
                die($e);
            }
        }

        $toCache = array_merge($toCache, $this->bootstrapManifestData());
        
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

        return;
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
        // $this->mtime_candidates = scandir(__ENV_ROOT__ . "/routes/");
        foreach ($this::__DEFINITIONS__ as $file) {
            $mtime = filemtime($file);
            if ($mtime === false) continue;
            if ($mtime > $max_m_time) $max_m_time = $mtime;
        }
        return $max_m_time;
    }

    public function getSettingDefinitions(){
        try {
            $this->raw_decode = [];
            foreach($this::__DEFINITIONS__ as $file) {
                if(!file_exists($file)) continue;
                $this->raw_decode[$file] = jsonc_decode(file_get_contents($file), true, 512, JSON_ERROR_SYNTAX);
            }

            $values = [];
            $definitions = [];
            foreach($this->raw_decode as $filename => $settings) {
                $this->parseSetting($values, $definitions, $settings, $filename);
            }
            // $json = get_all_where_available($this::__DEFINITIONS__, true, true);
        } catch (\Exception $e) {
            die($e->getMessage());
        } 
        $this->default_values = $values;
        $this->definitions    = $definitions;
    }

    private function parseSetting(&$values, &$def, $settings, $filename) {
        $detect_definition = ['default','directives','meta'];
        foreach($settings as $name => $data) {
            $isDefinition = $this->isDefinition($data);
            if(gettype($data) == "array") {
                if($isDefinition) {
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

    public function isDefinition($definition):bool {
        $detect_definition = ['default', 'directives', 'meta', 'validate'];
        // If it's not an array, it can't be a definition, return false
        if(gettype($definition) !== "array") return false;
        // intersect returns ['default'] <- is definition
        // intersect returns [] <- is not a definition
        // empty([]) // true, but it's NOT a definition
        // empty(['default']) // false, but it IS a definition
        // !empty() // to get the true status
        return !empty(array_intersect(array_keys($definition), $detect_definition));

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
        if(!$array[0]) {
            $this->insertOne([
                'Meta' => [
                    'type' => 'modified',
                    'max_m_mtime' => time(),
                ]
            ]);
            return $this->fetchModifiedSettings();
        }
        return iterator_to_array($array[0]);
    }

    public function bootstrapManifestData() {
        // TODO: remove TIME_TO_UPDATE global
        $GLOBALS['TIME_TO_UPDATE'] = true;
        $final = [];
        foreach($this::__MANIFESTS__ as $file) {
            foreach(get_json($file) as $type => $data) {
                $this->manifest_combine($type, $data, $final);
            }
        }
        foreach($final as $type => $data) {
            (!is_associative_array($data)) ? array_push($final[$type], ...$this->appendable[$type] ?? []) : $final[$type] = array_merge($final[$type], $this->appendable[$type]);
        }
        return $final;
    }

    protected $appendable = [];

    private function manifest_combine($name, $manifest, &$result) {
        foreach($manifest as $type => $val) {
            if($type === "common") continue;
            if($type === "append") continue;
            $index = "$name-$type";
            if(!isset($result[$index])) $result[$index] = $manifest['common'] ?? [];
            
            $array_type = is_associative_array($manifest['common'] ?? $result[$index]);
            
            if($array_type) $result[$index] = array_merge($result[$index], $val ?? []);
            else array_push($result[$index], ...array_values($val));

            if(isset($manifest['append'])) {
                if(!isset($this->appendable[$index])) $this->appendable[$index] = [];
                (!$array_type) ? array_push($this->appendable[$index], ...$manifest['append'] ?? []) : $this->appendable[$index] = array_merge($this->appendable[$index], $manifest['append'] ?? []);
            }

            array_unique($result[$index]);
        }
    }


    /** Update functions */
    public function update_setting($name, $value) {
        $value = $this->validate($name, $value);
        $isDefault = $this->is_default($name, $value);
        $m_time = time();
        $query = ['$set' => [$name => $value, "Meta.max_m_time" => $m_time]];
        if($isDefault) $query = ['$unset' => [$name => true]];
        $id = $this->__user_modified_settings['_id'];
        
        if(!$query['$set']) $query['$set'] = ["Meta.max_m_time" => $m_time];

        $result = $this->updateOne(['_id' => $id], $query);
        $this->bootstrap();
        return [$name => $this->__settings[$name]];
    }

    public function push($name, $value) {
        return $this->array_handler("push", $name, $value);
    }

    public function pull($name, $value) {
        return $this->array_handler("pull", $name, $value);
    }

    public function array_handler($method, $name, $value) {
        $value = $this->validate($name, $value);
        // if(gettype($this->__settings[$name]) !== "array") throw new \Exception("$name must be an array");
        
        // Get the existing setting from modified settings
        $id = $this->__user_modified_settings['_id'];
        
        // If the setting doesn't exist, set up to push/pull the value
        if(!isset($this->__user_modified_settings->$name)) {
            $this->updateOne(['_id' => $id], ['$set' => [$name => $this->__settings[$name]]]);
        }

        $m_time = time();
        $method = match($method) {
            'pull' => '$pull',
            'push' => '$addToSet',
        };
        $query = [
            $method => [$name => $value],
            '$set' => ['Meta.max_m_time' => $m_time],
        ];
        $result = $this->updateOne(['_id' => $id], $query);
        return [$name => $this->findOne(['_id' => $id])->{$name}];
    }

    public function reset_to_default($name) {
        if(!$this->is_setting($name)) throw new \Exception("Setting is not defined.");
        $query = ['$unset' => [$name => true]];

        $id = $this->__user_modified_settings['_id'];

        $result = $this->updateOne(['_id' => $id], $query);
        return [$name => $this->update_settings[$name]->defaultValue];
    }

    private function is_setting($name) {
        $this->bootstrap();
        $this->update_settings = $this->instances;
        return key_exists($name, $this->update_settings);
    }

    private function is_default($name, $value) {
        return ($value === $this->update_settings[$name]->defaultValue);
    }

    function check_type($validation, $name, $value) {
        $types = [
            "boolean",
            "integer",
            "double",
            "string",
            "array",
        ];

        if(!in_array($validation['type'],$types)) throw new Exception("Invalid type specified for setting");
        if(gettype($value) !== $validation['type']) throw new ValidationFailed("Invalid datatype");
    }

    function check_ctype($validation, $name, $value) {
        $ctypes = [
            'alnum' => 'ctype_alnum',
            'alpha' => 'ctype_alpha',
            'cntrl' => 'ctype_cntrl',
            'digit' => 'ctype_digit',
            'graph' => 'ctype_graph',
            'lower' => 'ctype_lower',
            'print' => 'ctype_print',
            'punct' => 'ctype_punct',
            'space' => 'ctype_space',
            'upper' => 'ctype_upper',
            'xdigit' => 'ctype_xdigit',
        ];
        if(!$ctypes[$validation['ctype']]($value)) throw new ValidationFailed("Ctype validation failed");
    }

    function filter($filters, $name, $value) {
        if(gettype($filters) !== "array") $filters = [$filters => []];

        $mutant = $value;
        foreach($filters as $filter => $flags) {
            $f = 0;
            foreach($flags as $flag) {
                $f &= constant($flag);
            }
            $f &= FILTER_NULL_ON_FAILURE;
            $mutant = filter_var($mutant, constant($filter), $f);
            if($mutant === null) throw new ValidationFailed("Filtering process failed.");
        }

        return $mutant;
    }

    private function validate($name, $value) {
        if(!$this->is_setting($name, $value)) throw new ValidationIssue("Setting is not defined.");

        $v = $this->instances[$name]->validate;

        if(isset($v['confirm'])) confirm($v['confirm'], [$name => $value]);
        if(isset($v['type'])) $this->check_type($v, $name, $value);
        if(isset($v['ctype'])) $this->check_ctype($v, $name, $value);
        
        
        if(isset(($v['filter']))) $value = $this->filter($v['filter'], $name, $value);

        return $value;
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
