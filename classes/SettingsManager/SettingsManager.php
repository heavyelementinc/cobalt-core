<?php

/**
 * The SettingsManager class should only need to exist ONCE for each session. 
 * The default_settings file defines meta information about each setting. This 
 * file may define directives to be handled by this class upon instantiation.
 * 
 * ========================================
 * Valid directive keywords are as follows:
 * ========================================
 * 
 *   default   - The default value of this setting. If it's not specified by the
 *               app settings, this will be the setting's value
 * 
 *               NOTE that the default directive DOES NOT start with a '$'
 * 
 *   $env      - The $env directive will import an environment variable IF IT 
 *               EXISTS using the directive's value as the name of the env 
 *               variable. If the env variable is _not_ specified, then this
 *               directive will FAIL SILENTLY and inherit its value from the 
 *               app's settings OR the default definition.
 *              
 *               NOTE that if the variable DOES exist, it will OVERRIDE whatever
 *               app settings might exist.
 * 
 *   $loadJSON - If the $loadJSON key is found and set to `true` or `false`, the
 *               value of 'default' or the value of app_setting will be used as 
 *               a path name, with $loadJSON used as the second argument for 
 *               json_decode.
 * 
 *               NOTE that an SettingsManagerException will be thrown if
 *               pathname does not exist and a JSON parse exception will be 
 *               thrown if there is a problem parsing the JSON.
 * 
 *               ALSO NOTE that pathnames which do not begin with a `/` will 
 *               have either the __ENV_ROOT__ or __APP_ROOT__ prepended to them 
 *               depending on which file specified the path.
 * 
 *   $alt      - (String) JS object path to another ALREADY DEFINED setting 
 *               value. $alt will check if the current value is a string and if 
 *               that string is empty it will reference the name of the value 
 *               specified using JS syntax.
 *               
 *               This is meant to allow certain settings to be left out of the 
 *               app's settings and still inherit a value relevant to the app.
 *
 *   $combine  - Processes the associated array of strings and combines them 
 *               into a STRING, look at the the value of each string to see if 
 *               there's a matching value defined in this class.
 *
 *               If the VALUE of the property before the $combine operation is 
 *               bool false, the combine operation is ignored and an empty 
 *               string is provided instead.
 * 
 * ARRAY HANDLING
 * ==============
 * 
 *   $prepend  - Prepends the default values with the app's values. An array 
 *               with unique entries will be stored as the value of the setting.
 * 
 *   $merge    - Merges the 'default' of the default settings and the value of 
 *               the app setting with app settings taking precedence.
 *
 *   $mergeAll - Recursively merges the 'default' of default settings and the 
 *               value of the app setting with app settings taking precedence.
 *
 *   $push     - Accepts an array of variable names, will append those variables
 *               to the end of the default and app's specified array
 * 
 * MISC HANDLING
 * =============
 *
 *   $required - This directive accepts a key => pair value of Setting_name => 
 *               bool value and will compare the value of Setting_name to the 
 *               bool specified. If the comparison FAILS, the current setting 
 *               will be set to the value of on_fail_value or false if no 
 *               on_fail_value is supplied. If the comparison for all required 
 *               settings succeeds, either the app's setting OR the default 
 *               value will be allowed to stand.
 * 
 *               on_fail_value should be supplied as a key in the $required
 *               directive. It doesn't matter what order the list is supplied.
 * 
 *               NOTE: non-boolean values of $required settings may fail the 
 *               check. Please supply an on_fail_value for non-boolean values.
 * 
 *   $public   - If truthy, this setting is exposed to the client as JavaScript.
 *               Should be last directive.
 * 
 * ==============================
 * Defining App-specific Settings
 * ==============================
 * 
 * Follow the same syntax as the default_settings file. The new setting 
 * definition must:
 * 
 *   #1 Have a unique name--not otherwise defined in default_settings
 *   #2 MUST have a 'default' directive--even if other directives override its 
 *      value
 * 
 * Unrecognized settings in tmp_app_setting_values lacking definition directives will 
 * throw a warning.
 */

require_once __DIR__ . "/SettingsManagerException.php"; // Just in case we need to throw an exception

class SettingsManager {
    /** Allow the SettingsManager to "compile" the app's settings with the 
     * defaults and restore them from the "compiled" version later. */
    private $enable_settings_from_cache = true;
    /** The path to the default setting definitions file. */
    private $path_to_settings_definitions_file = __ENV_ROOT__ . "/config/setting_definitions.jsonc";

    /** The parsed default settings */
    private $setting_definitions = [];

    /** Possible files containing app settings */
    private $app_paths_settings = [
        __APP_ROOT__ . "/private/config/settings.json",  // App's settings
        __APP_ROOT__ . "/ignored/config/settings.json", // .gitignored file can override APP settings
    ];

    /** Filename where the "compiled" settings should be saved to */
    private $app_cache_filename = "config/settings.json";

    /** Decoded value  */
    private $tmp_app_setting_values = [];

    // Public settings are exposed to the client as a JavaScript Object Literal and with every API
    // call which has the "X-Update-Client-State" header set to "true"
    // TODO: API needs to handle "X-Update-Client-State" header!
    public $public_settings = [];
    public $root_style_definition = "";

    /** ===============
     *  Manage settings
     *  ===============
     */

    /** SettingsManager will load and construct our settings either from a
     * previous "compiled" cache or by "compiling" our settings using the setting
     * definitions file as a list of instructions (directives).
     * 
     * @param bool $cache - `true` to enable caching (default's to true)
     * @return object
     */
    function __construct($cache = true) {
        $this->enable_settings_from_cache = $cache;
        $this->settings = new \SettingsManager\Settings();

        // Check if the core settings file exists
        if (!file_exists($this->path_to_settings_definitions_file)) throw new SettingsManagerException("No core settings file found");

        // Import our settings definitions
        $json = file_get_contents($this->path_to_settings_definitions_file);
        // // Strip all comments from the settings
        // $json = preg_replace( '/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/m' , '' , $json);

        try {
            // Try to decode our settings definitions.
            $this->setting_definitions = jsonc_decode($json, true, 512, JSON_THROW_ON_ERROR);

            $plugin_settings = [];
            foreach ($GLOBALS['ACTIVE_PLUGINS'] as $plugin) {
                $result = $plugin->register_settings();
                if ($result) array_push($plugin_settings, $result);
            }
            $this->setting_definitions = array_merge($this->setting_definitions, ...$plugin_settings);
        } catch (Exception $e) {
            die("Syntactic error in setting definitions file");
        }

        // Check if we need to import our settings from the cache
        $this->got_from_cache = $this->from_cache();
        if ($this->enable_settings_from_cache && $this->got_from_cache) { // Settings ARE available from the cache
            $GLOBALS['time_to_update'] = false;
            // Load the cached settings file
            $settings = $this->cache_resource->get("json");

            $this->tmp_app_setting_values = $settings['app']; // Restore our app settings 
            $this->public_settings = $settings['public']; // Restore our public settings
            $this->root_style_definition = $settings['style']; // Restore our root style definitions

            // Import every key into the settings object
            foreach ($this->tmp_app_setting_values as $name => $setting) {
                $this->set($name, $setting);
            }
            $this->settings->set_index(array_keys($this->setting_definitions));
            return $this;
        }

        $GLOBALS['time_to_update'] = true;
        // Load all the settings files
        $this->load_settings();
        // Process the settings
        $this->process();
        $settings = $this->get_settings();
        $this->cache_resource->set([
            'app' => $settings,
            'public' => $this->public_settings,
            'style' => $this->root_style_definition
        ], true);
    }

    /** Checks file modified times for all settings files (including definitions) 
     * and returns a "false" if any of them have a newer modified time than our
     * cached version.
     * 
     * @return bool
     */
    function from_cache() {
        $this->cache_resource = new \Cache\Manager($this->app_cache_filename);
        $res = [$this->path_to_settings_definitions_file, ...$this->app_paths_settings];

        foreach ($res as $file) {
            if (!file_exists($file)) continue;
            if ($this->cache_resource->last_modified <= filemtime($file)) return false;
        }
        return true;
    }

    /** Loads and parses JSON files if they exist 
     *
     * @return null
     */
    function load_settings() {

        foreach ($this->app_paths_settings as $path) {
            if (!file_exists($path)) continue; // Skip this file, it doesn't exist.
            // Load JSON file and decode it
            // Shuld this be jsonc_decode?
            if (!$raw = file_get_contents($path)) continue;
            $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            // Check if the app settings file exists and decode it (it should be
            // okay if the app settings don't exist)
            $this->tmp_app_setting_values = array_merge($this->tmp_app_setting_values, $json);
        }
    }

    function process() {
        // If we're defining a new setting in our app settings, we need to
        // integrate that into our default_settings before we continue
        foreach ($this->tmp_app_setting_values as $k => $v) {
            // Check if the key does not exist in default, check its type and 
            // look for a 'default'
            if (!key_exists($k, $this->setting_definitions)) {
                $this->process_app_definitions($k, $v);
            }
        }

        try {
            // Loop through our settings file
            foreach ($this->setting_definitions as $setting_name => $directives) {
                $this->process_directives($setting_name, $directives);
            }
        } catch (SettingsManagerException $e) {
            die($e->getMessage());
        }

        $this->settings->set_index(array_keys($this->setting_definitions));
    }

    function process_app_definitions($k, $v) {
        if (gettype($v) !== "array" || !key_exists('default', $v)) {
            trigger_error("$k is not a recognized setting and lacks proper definition directives.", E_USER_WARNING);
            return;
        }
        // Add the app's definitions to our list of definitions.
        $this->setting_definitions[$k] = $v;

        // Then set the app_setting to the default value.
        $this->tmp_app_setting_values[$k] = $v['default'];
    }

    function process_directives($setting_name, $directives) {
        // Loop through our directives
        foreach ($directives as $directive => $value) {
            if ($directive === "default") {
                $v = $this->tmp_app_setting_values[$setting_name] ?? $value;
                // if(isset($this->tmp_app_setting_values[$setting_name])) $v = ;
                $this->set($setting_name, $v);
            }

            // Check if our directive starts with a $
            if ($directive[0] !== "$") continue;

            // If it does, remove the dollar sign and check if the method exists
            $directive = substr($directive, 1);
            if (!method_exists($this, $directive)) throw new SettingsManagerException("Directive $directive does not have a corresponding method.");

            // Execute the method and store the return value as the setting
            $this->{$directive}($value, $directives, $setting_name);

            continue; // Skip setting the default value
        }
    }

    function get($setting_name) {
        if (!isset($this->settings->{$setting_name})) return null;
        return $this->settings->{$setting_name};
    }

    function set($setting_name, $value) {
        /** Check if the key exists, if it does, set a matching propety in this class with the value
         * stored in tmp_app_setting_values[$setting_name] and return
         * 
         * Otherwise, assign the default.
         */
        // if(key_exists($setting_name,$this->tmp_app_setting_values)) {
        //     $this->get($setting_name) = $this->tmp_app_setting_values[$setting_name];
        //     return;
        // }
        $this->settings->{$setting_name} = $value;
    }

    /** ==================
     *  Directive Handlers
     *  ==================
     */

    function env($reference, $directives, $setting_name) {
        if (!getenv($reference)) return;
        $this->set($setting_name, getenv($reference));
    }

    function alt($reference, $directives, $setting_name) {
        /** Get the value we already have assigned */
        $value = $this->get($setting_name);
        if (!key_exists($setting_name, $this->tmp_app_setting_values)) $value = "";
        /** Check its type and see if it's a string. If it's not, do nothing */
        $type = gettype($value);
        if ($type !== "string") return;
        /** Check if the value is empty */
        if (!empty($value)) return;
        /** Look up the value of our */
        $value = lookup_js_notation($reference, $this->settings, false);
        $this->set($setting_name, $value);
    }

    function push($reference, $directives, $setting_name) {
        $mutant = [];
        foreach ($reference as $ref) {
            $value = lookup_js_notation($ref, $this->settings, false);
            // if(is_string($value)) $value = [$value];
            array_push($mutant, $value);
        }
        $mutant = array_unique(array_merge($directives['default'], $this->get($setting_name), $mutant));
        $this->set($setting_name, $mutant);
    }

    function merge($value, $directives, $setting_name) {
        $apps = [];
        if (key_exists($setting_name, $this->tmp_app_setting_values)) $apps = $this->tmp_app_setting_values[$setting_name];
        $this->set($setting_name, array_merge($directives['default'], $apps));
    }

    function mergeAll($value, $directives, $setting_name) {
        $apps = [];
        if (key_exists($setting_name, $this->tmp_app_setting_values)) $apps = $this->tmp_app_setting_values[$setting_name];
        $this->set($setting_name, array_merge_recursive($directives['default'], $apps));
    }

    function prepend($value, $directives, $setting_name) {
        $setting = $this->get($setting_name);
        if ($directives['default'] === $setting) return; // If the arrays are the same, do nothing

        if (!is_array($setting)) throw new SettingsManagerException("The values provided by the app's $setting_name are not an array.");
        /** Merge the values of the APP (stored in $this->get($setting_name) ) with the default values */
        $this->set($setting_name, array_unique(array_merge($setting, $this->meta['default'])));
    }

    function loadJSON($value, $directives, $setting_name) {
        if (!is_bool($value)) throw new SettingsManagerException("The value provided for $setting_name.\$loadJSON is invalid (must be bool). If you want to specify the path, it should be stored at $setting_name.default or as $setting_name in app settings file.");
        $root = __ENV_ROOT__ . "/"; // Set our root location
        $path_name = null;
        // As specified in the above documentation, this directive uses the 'default' value for
        // as our pathname or, where available, the app's specific pathname
        if (key_exists('default', $directives)) $path_name = $directives['default'];
        if (key_exists($setting_name, $this->tmp_app_setting_values)) {
            $root = __APP_ROOT__ . "/";
            $path_name = $this->tmp_app_setting_values[$setting_name];
        }
        if ($path_name === null) throw new SettingsManagerException("The app must specifiy a file to load! ($setting_name)");
        if ($path_name[0] !== "/") $path_name = $root . $path_name;
        if (!file_exists($path_name)) throw new SettingsManagerException("File does not exist: $path_name");
        $this->set($setting_name, json_decode(file_get_contents($path_name), $value, 512, JSON_THROW_ON_ERROR));
    }

    /** The $public directive should (probably) be the last directive */
    function public($value, $directives, $setting_name) {
        if ($value !== true) {
            trigger_error("The \$public directive must be set to `true` to expose $setting_name to clients", E_USER_WARNING);
            return;
        }
        if (property_exists($this->settings, $setting_name)) $value = $this->get($setting_name);
        else $value = $directives['default'];
        $this->public_settings[$setting_name] = $value; // Add the value to the public settings
    }

    function style($value, $directives, $setting_name) {
        $setting = $this->get($setting_name);
        if ($setting_name === "fonts") {
            foreach ($setting as $type => $v) {
                $this->root_style_definition .= "--project-$type-family: $v[family];\n";
            }
            return;
        }

        if ($setting_name === "css-vars") {
            foreach ($setting as $type => $v) {
                $this->root_style_definition .= "--project-$type: $v;\n";
            }
            return;
        }

        $this->root_style_definition .= "--project-$setting_name: " . $this->get($setting_name) . ";\n";
    }

    function combine($combination_array, $directives, $setting_name) {
        if ($this->get($setting_name) === false) {
            $this->set($setting_name, "");
            return;
        }
        $mutant = "";
        foreach ($combination_array as $v) {
            $property = $this->get($v);
            if ($v === '$default') $mutant .= $directives['default'];
            else if ($property !== null) $mutant .= $property;
            else $mutant .= $v;
        }
        $this->set($setting_name, $mutant);
    }

    function required($value, $directives, $setting_name) {
        // The on_fail_value is not required for this method and will default to
        // false.
        $on_fail_value = $value['on_fail_value'] ?? false;
        unset($value['on_fail_value']);

        /** Loop through the required settings */
        foreach ($value as $k => $v) {
            $set_value = $this->get($k);
            /** If the setting is not equal to the value provided */
            if ($set_value !== null && $set_value !== $v) {
                /** Set the CURRENT setting to on_fail_value */
                $this->set($setting_name, $on_fail_value);
                return;
            }
        }
        /** Check if the app has already defined this setting and if not, set the
         *  value to default */
        if ($this->get($setting_name) === null) $this->set($setting_name, $directives['default']);
    }

    function method($value, $directives, $setting_name) {
        if (!method_exists($this, $value)) throw new SettingsManagerException("$setting_name's specified method doesn't exist");
        $this->set($setting_name, $this->{$value}($value, $directives, $setting_name));
    }

    /** =============
     *  Other methods 
     *  =============
     */

    function set_packages($value, $directives, $setting_name) {
        // Hard code Router.js, main.js, and app.js
        return array_merge($this->get($setting_name), ['Router.js', 'main.js', 'app.js']);
    }

    /** Gets the entire list of settings for the app. */
    function get_settings() {
        // $settings = [];
        // foreach($this->setting_definitions as $setting_name => $value){
        //     $settings[$setting_name] = $this->get($setting_name);
        // }
        return iterator_to_array($this->settings);
    }

    function get_public_settings() {
        return $this->public_settings;
    }
}
