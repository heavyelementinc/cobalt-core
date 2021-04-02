<?php
/**
 * The SettingsManager class should only need to exist ONCE for each session. The default_settings
 * file defines meta information about each setting. This file may define directives to be handled
 * by this class upon instantiation.
 * 
 * ========================================
 * Valid directive keywords are as follows:
 * ========================================
 * 
 *   default   - The default value of this setting. If it's not specified by the app settings, this will
 *                be the setting's value
 * 
 *               NOTE that the default directive DOES NOT start with a '$'
 * 
 *   $env      - The $env directive will import an environment variable IF IT EXISTS using the directive's
 *                value as the name of the env variable. If the env variable is _not_ specified, then this
 *                directive will FAIL SILENTLY and inherit its value from the app's settings OR the default
 *                definition.
 *              
 *               NOTE that if the variable DOES exist, it will OVERRIDE whatever app settings might exist.
 * 
 *   $loadJSON - If the $loadJSON key is found and set to `true` or `false`, the value of 'default' or 
 *                the value of app_setting will be used as a path name, with $loadJSON used as the second
 *                argument for json_decode.
 * 
 *               NOTE that an SettingsManagerException will be thrown if pathname does not exist and a
 *                JSON parse exception will be thrown if there is a problem parsing the JSON.
 * 
 *               ALSO NOTE that pathnames which do not begin with a `/` will have either the __ENV_ROOT__
 *                or __APP_ROOT__ prepended to them depending on which file specified the path.
 * 
 *   $alt      - (String) JS object path to another ALREADY DEFINED setting value. $alt will check if the
 *                current value is a string and if that string is empty it will reference the name of the
 *                value specified using JS syntax.
 *               
 *               This is meant to allow certain settings to be left out of the app's settings and still
 *                inherit a value relevant to the app.
 *
 *   $combine  - Processes the associated array of strings and combines them into a STRING, look at the
 *                the value of each string to see if there's a matching value defined in this class.
 *
 *               If the VALUE of the property before the $combine operation is bool false, the combine
 *                operation is ignored and an empty string is provided instead.
 * 
 * ARRAY HANDLING
 * ==============
 * 
 *   $prepend  - Prepends the default values with the app's values. An array with unique entries will be
 *                stored as the value of the setting.
 * 
 *   $merge    - Merges the 'default' of the default settings and the value of the app setting with app
 *                settings taking precedence.
 *
 *   $mergeAll - Recursively merges the 'default' of default settings and the value of the app setting
 *                with app settings taking precedence.
 *
 *   $add      - Accepts an array of variable names, will append those variables to the end of the default
 *                and app's specified array
 * 
 * MISC HANDLING
 * =============
 *
 *   $required - This directive accepts a key => pair value of Setting_name => bool value and will compare
 *                the value of Setting_name to the bool specified. If the comparison FAILS, the current
 *                setting will be set to false. If the comparison for all required settings succeeds, either
 *                the app's setting OR the default value will be allowed to stand.
 * 
 *               NOTE: non-boolean values of $required settings may fail the check and result in a boolean
 *                value if the check fails. This directive is meant for use with normally boolean.
 * 
 *   $public   - If truthy, this setting is exposed to the client as JavaScript. Should be last directive.
 * 
 * ==============================
 * Defining App-specific Settings
 * ==============================
 * 
 * Follow the same syntax as the default_settings file. The new setting definition must:
 * 
 *   #1 Have a unique name--not otherwise defined in default_settings
 *   #2 MUST have a 'default' directive--even if other directives override its value
 * 
 * Unrecognized settings in app_settings lacking definition directives will throw a warning.
 */

require_once __DIR__ . "/SettingsManagerException.php"; // Just in case we need to throw an exception

class SettingsManager implements Iterator{
    private $enable_settings_from_cache = true;
    private $default_settings_path = __ENV_ROOT__ . "/config/setting_definitions.json";
    private $default;

    private $app_settings_path = [
        __ENV_ROOT__ . "/ignored/config/settings.json",
        __APP_ROOT__ . "/ignored/config/settings.json",
        __APP_ROOT__ . "/private/config/settings.json"
    ];
    private $app_cache_filename = "config/settings.json";
    private $app_settings = [];
    
    // Public settings are exposed to the client as a JavaScript Object Literal and with every API
    // call which has the "X-Update-Client-State" header set to "true"
    // TODO: API needs to handle "X-Update-Client-State" header!
    public $public_settings = [];
    public $root_style_definition = "";

    /** ===============
     *  Manage settings
     *  ===============
     */
    function __construct($cache = true){
        $this->enable_settings_from_cache = $cache;
        // Check if the core settings file exists
        if(!file_exists($this->default_settings_path)) throw new SettingsManagerException("No core settings file found");

        // Import and decode our settings definitions.
        $this->default = json_decode(file_get_contents($this->default_settings_path),true,512,JSON_THROW_ON_ERROR);
        
        // Check if we need to import our settings from the cache
        $this->got_from_cache = $this->from_cache();
        if($this->enable_settings_from_cache && $this->got_from_cache){ // Settings ARE available from the cache
            $GLOBALS['time_to_update'] = false;
            // Load the cached settings file
            $settings = $this->cache_resource->get("json");

            $this->app_settings = $settings['app']; // Restore our app settings 
            $this->public_settings = $settings['public']; // Restore our public settings
            $this->root_style_definition = $settings['style']; // Restore our root style definitions

            // Import every key into the settings object
            foreach($this->app_settings as $name => $setting){
                $this->set($name,$setting);
            }
        } else { // Settings DO NOT exist.
            $GLOBALS['time_to_update'] = true;
            // Load all the settings files
            $this->load_settings();
            // Process the settings
            $this->process();
            $this->cache_resource->set([
                'app' => $this->get_settings(),
                'public' => $this->public_settings,
                'style' => $this->root_style_definition
            ],true);
        }
        $this->index = array_keys($this->default);
    }

    function __destruct(){

    }

    function from_cache(){
        $this->cache_resource = new \Cache\Manager($this->app_cache_filename);
        $res = [$this->default_settings_path,...$this->app_settings_path];

        foreach($res as $file){
            if(!file_exists($file)) continue;
            if($this->cache_resource->last_modified <= filemtime($file)) return false;
        }
        return true;
    }

    function load_settings(){
        foreach($this->app_settings_path as $path){
            // Check if the app settings file exists and decode it (it should be okay if the app settings don't exist)
            if(file_exists($path)) $this->app_settings = array_merge($this->app_settings,json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR));
        }
    }

    function process(){
        // If we're defining a new setting in our app settings, we need to integrate that
        // into our default_settings before we continue
        foreach($this->app_settings as $k => $v){
            // Check if they key does not exist in default, check its type and look for a 'default'
            if(!key_exists($k,$this->default)){
                if(gettype($v) === "array" && key_exists('default',$v)) {
                    // Add the definition to our defaults
                    $this->default[$k] = $v;
                    // Then overwrite the app_setting to the default value.
                    $this->app_settings[$k] = $v['default'];
                } else trigger_error("$k is not a recognized setting and lacks proper definition directives.",E_USER_WARNING);
            }
        }

        /** TODO: HIDEOUS NESTING! FIX THIS! */
        try{
            // Loop through our settings file
            foreach($this->default as $key => $meta){
                // Loop through our directives
                foreach($meta as $directive => $value){
                    if($directive === "default") $this->set($key,$value);

                    // Check if our directive starts with a $
                    if($directive[0] !== "$") continue;

                    // If it does, remove the dollar sign and check if the method exists
                    $directive = substr($directive,1);
                    if(!method_exists($this,$directive)) throw new SettingsManagerException("Directive $directive does not have a corresponding method.");
                    // Execute the method and store the return value as the setting
                    $this->{$directive}($value,$meta,$key);
                    continue; // Skip setting the default value
                }
            }
        } catch(SettingsManagerException $e){
            die($e->getMessage());
        }
    }

    function set($key,$value){
        /** Check if the key exists, if it does, set a matching propety in this class with the value
         * stored in app_settings[$key] and return
         * 
         * Otherwise, assign the default.
         */
        if(key_exists($key,$this->app_settings)) {
            $this->{$key} = $this->app_settings[$key];
            return;
        }
        $this->{$key} = $value;
    }

    /** ==================
     *  Directive Handlers
     *  ==================
     */

    function env($reference, $meta, $key){
        if(!getenv($reference)) return;
        $this->{$key} = getenv($reference);
    }

    function alt($reference, $meta, $key){
        /** Get the value we already have assigned */
        $value = $this->{$key};
        if(!key_exists($key,$this->app_settings)) $value = "";
        /** Check its type and see if it's a string. If it's not, do nothing */
        $type = gettype($value);
        if($type !== "string") return;
        /** Check if the value is empty */
        if(!empty($value)) return;
        /** Look up the value of our */
        $value = lookup_js_notation($reference,$this,false);
        $this->{$key} = $value;
    }

    function add($reference, $meta, $key){
        $mutant = [];
        foreach($reference as $ref){
            $value = lookup_js_notation($ref,$this,false);
            // if(is_string($value)) $value = [$value];
            array_push($mutant,$value);
        }
        $mutant = array_unique(array_merge($meta['default'],$this->{$key},$mutant));
        $this->{$key} = $mutant;
    }

    function merge($value, $meta, $key){
        $apps = [];
        if(key_exists($key,$this->app_settings)) $apps = $this->app_settings[$key];
        $this->{$key} = array_merge($meta['default'],$apps);
    }

    function mergeAll($value, $meta, $key){
        $apps = [];
        if(key_exists($key,$this->app_settings)) $apps = $this->app_settings[$key];
        $this->{$key} = array_merge_recursive($meta['default'],$apps);
    }

    function prepend($value,$meta,$key){
        if($meta['default'] === $this->{$key}) return; // If the arrays are the same, do nothing
        
        if( !is_array($this->{$key}) ) throw new SettingsManagerException("The values provided by the app's $key are not an array.");
        /** Merge the values of the APP (stored in $this->{$key} ) with the default values */
        $this->{$key} = array_unique(array_merge($this->{$key},$this->meta['default']));
    }

    function loadJSON($value, $meta, $key){
        if(!is_bool($value)) throw new SettingsManagerException("The value provided for $key.\$loadJSON is invalid (must be bool). If you want to specify the path, it should be stored at $key.default or as $key in app settings file.");
        $root = __ENV_ROOT__ . "/"; // Set our root location
        $path_name = null; 
        // As specified in the above documentation, this directive uses the 'default' value for
        // as our pathname or, where available, the app's specific pathname
        if(key_exists('default',$meta)) $path_name = $meta['default'];
        if(key_exists($key,$this->app_settings)) {
            $root = __APP_ROOT__ . "/";
            $path_name = $this->app_settings[$key];
        }
        if($path_name === null) throw new SettingsManagerException("The app must specifiy a file to load! ($key)");
        if($path_name[0] !== "/") $path_name = $root . $path_name;
        if(!file_exists($path_name)) throw new SettingsManagerException("File does not exist: $path_name");
        $this->{$key} = json_decode(file_get_contents($path_name),$value,512,JSON_THROW_ON_ERROR);
    }

    /** The $public directive should (probably) be the last directive */
    function public($value, $meta, $key){
        if($value !== true) {
            trigger_error("The \$public directive must be set to `true` to expose $key to clients",E_USER_WARNING);
            return;
        }
        if(property_exists($this,$key)) $value = $this->{$key};
        else $value = $meta['default'];
        $this->public_settings[$key] = $value; // Add the value to the public settings
    }

    function style($value, $meta, $key){
        if($key === "fonts"){
            foreach($this->{$key} as $type => $v){
                $this->root_style_definition .= "--project-$type-family: $v[family];\n";
            }
            return;
        }

        if($key === "css-vars"){
            foreach($this->{$key} as $type => $v){
                $this->root_style_definition .= "--project-$type: $v;\n";
            }
            return;
        }
        
        $this->root_style_definition .= "--project-$key: ".$this->{$key}.";\n";
    }

    function combine($combination_array,$meta,$key){
        if($this->{$key} === false ){
            $this->{$key} = "";
            return;
        }
        $mutant = "";
        foreach($combination_array as $v){
            if ($v === '$default') $mutant .= $meta['default'];
            else if(property_exists($this,$v)) $mutant .= $this->{$v};
            else $mutant .= $v;
        }
        $this->{$key} = $mutant;
    }

    function required($value,$meta,$key){
        /** Loop through the required settings */
        foreach($value as $k => $v){
            /** If the setting is not equal to the value */
            if($this->{$k} !== $v) {
                /** Set the CURRENT setting to false and return */
                $this->{$key} = false;
                return;
            }
        }
        /** Check if the app has already defined this setting and if not, set th value to default */
        if(!isset($this->{$key})) $this->{$key} = $meta['default'];
    }

    /** =============
     *  Other methods 
     *  =============
     */

    function get_settings(){
        $settings = [];
        foreach($this->default as $key => $value){
            $settings[$key] = $this->{$key};
        }
        return $settings;
    }

    function get_public_settings(){
        return $this->public_settings;
    }

    function cleanup(){
        return true;
    }

    /** ==============
     *  Iterator Stuff 
     *  ==============
     */
    private $pointer = 0;

    public function current(){
        return $this->{$this->index[$this->pointer]};
    }

    public function key(){
        return $this->index[$this->pointer];
    }

    public function next(){
        $this->pointer++;
    }

    public function rewind(){
        $this->pointer = 0;
    }

    public function valid(){
        return isset($this->index[$this->pointer]);
    }
}