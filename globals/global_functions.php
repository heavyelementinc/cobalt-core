<?php

/** A shorthand way of getting a specific setting by providing the name of the setting
 * as the only argument, calling this function without an argument will return all
 * the settings.
 */
function app($setting = null){
    if($setting === null) return __APP_SETTINGS__;
    if(key_exists($setting,__APP_SETTINGS__)) return __APP_SETTINGS__[$setting];
    trigger_error("Setting $setting does not exist",E_USER_ERROR);
    // throw new Warning("Setting $setting does not exist");
}

/** A shorthand way of accessing the current user's session from anywhere in the program
 * Ideally, this should work like the app function and provide everything about the
 * user when called with no agruments.
 * 
 * TODO: Implement user accounts and sessions.
 */
function session($name = null){
    if($name === null) return $GLOBALS['session'];
    if(key_exists($name,$GLOBALS['session'])) return $GLOBALS['session'][$name];
    throw new Exception("Field $name does not exist");
}

function session_exists(){
    if(isset($GLOBALS['session']) && $GLOBALS['session'] === null) return false;
    if(isset($GLOBALS['session']) && !empty($GLOBALS['session']['pword'])) return true;
    return false;
}

function has_permission($perm_name,$group = null){
    return $GLOBALS['auth']->has_permission($perm_name,$group);
}

/** This function will return a merged array of decoded JSON files that are found to
 * exist. Later elements in the $paths argument will overwrite earlier elements of
 * the same name.
 * 
 * If $merged is false, the decoded files will be returned as separate elements of
 * the array.
 */
function get_all_where_available($paths,$merged = true){
    $available = [];
    foreach($paths as $key => $path){
        if(file_exists($path)) $available[$key] = json_decode(file_get_contents($path),true);
    }
    if($merged) return array_merge(...$available);
    return $available;
}

/** Hand this function an array of files that might exist and this function will
 *  return an array of file paths that exist */
function files_exist_the_hard_way($arr,$error_on_empty = true){
    $result = [];
    foreach($arr as $file){
        if(file_exists($file)) array_push($result,$file);
    }
    if($error_on_empty && empty($result)) throw new Exception(__FUNCTION__ . " requires that it find at least one file that exists.");
    return $result;
}

/** Hand this function an array of files that might exist and this function will
 * return an array of file paths that exist. If TRUE is used as the second argument
 * and no files are found, an exception will be thrown.
 */
function files_exist($arr,$error_on_empty = true){
    $values = array_values(array_filter($arr,"file_exists"));
    if($error_on_empty && empty($values)) throw new Exception(__FUNCTION__ . " requires that it find at least one file that exists.");
    return $values;
}


function template_exists($template){
    $file = count(files_exist([
        __APP_ROOT__ . "/private/templates/$template",
        __ENV_ROOT__ . "/templates/$template"
    ],false));
    return (bool)$file;
}

/** The autoload routine of for our classes. */
function cobalt_autoload($class){
    $namespace_to_path = str_replace("\\","/",$class) . ".php";
    $file = [];
    $file = files_exist([
        __APP_ROOT__ . "/private/classes/$namespace_to_path",
        __ENV_ROOT__ . "/classes/$namespace_to_path"
    ],false);
    
    if(count($file) >= 1) {
        require_once $file[0];
        return;
    }
    
    // Load class databases
    if(!isset($GLOBALS['class_directory'])) $GLOBALS['class_directory'] = get_all_where_available([__ENV_ROOT__ . '/classes/class_directory.json',__APP_ROOT__ . '/private/classes/class_directory.json']);
    $load = null;
    // Check if the class we're trying to load exists in the classes property
    if( key_exists($class,$GLOBALS['class_directory']['classes']) ){
        $load = $GLOBALS['class_directory']['classes'][$class];
    }
    if($class[0] === "\\") $class = substr($class,1);
    $explode = explode("\\",$class);
    $match_pattern = "/(.*)\\(\w+$)/";
    if(count($explode) === 2) {
        $namespace = $explode[0];
        $class_name = $explode[1];
        if( key_exists($namespace,$GLOBALS['class_directory']['namespaces']) ){
            $load = $GLOBALS['class_directory']['namespaces'][$namespace];
        }
    }

    // Throw an error if we don't have a load candidate
    if($load === null) throw new Exception("Could not load $class");
    
    // If the path key exists, process the strings and require the file
    if(key_exists('path',$load)) {
        $final_name = str_replace(
            ['__ENV_CLASSES__','__APP_CLASSES__'],
            [__ENV_ROOT__ . "/classes/",__APP_ROOT__ , "/private/classes/"],
            $load['path']
        );
        if(pathinfo($final_name,PATHINFO_EXTENSION) !== "php") $final_name .= "$class_name.php";
        require_once $final_name;
        return;
    }
}

function add_template($path){
    // if($GLOBALS['route_context'] !== "web") throw new Exception("You're not in the correct context to be adding templates like this!");
    $GLOBALS['web_processor_template'] = $path;
}

function add_vars($vars){
    // if($GLOBALS['route_context'] !== "web") throw new Exception("You're not in the correct context to be adding variables like this!");
    $GLOBALS['web_processor_vars'] = $vars;
}

/** 
 * This function accepts a JS object notated $path_map and searches $vars for a value
 * which matches $path_map
 *  $path_map = "map.get.item"
 *  $vars = ['map' => ['get' => ['item' => 'value']]]
 *  "value" // Result
 * 
 *  $path_map requires a string and should be in "dot.notation" format
 *  $vars can be an array or object
 *  $throw_on_fail should be set to false, true, or "warn"
 */
function lookup_js_notation(String $path_map,$vars,$throw_on_fail = false){
    $mutant = $vars;
    $separated = explode(".",$path_map);
    $looked_up = "";

    foreach($separated as $key){
        $type = gettype($mutant); // Get the type of the mutant
        /** If we have updated the mutant this iteration, $break will be set 
         * to false. */
        $break = true;

        /** If it's an array, we'll check if the key exists and set the value
         * of $mutant to the found value */
        if($type === "array") {
            if(!key_exists($key,$mutant)) break; // Break if we can't find the key
            $mutant = $mutant[$key];
            $break = false;
        }

        /** If it's an object, we'll check if the key exists and set the value
         * of $mutant to the found property */
        if($type === "object") {
            if(!property_exists($mutant,$key)) break; // Break if we can't find the property
            $mutant = $mutant->{$key};
            $break = false;
        }

        /** If we didn't update our mutant this iteration, then we need to break. */
        if($break) break;
        
        /** Update the pathname so we can check if we found the correct path. */
        $looked_up .= "$key.";

    }

    /** We're adding a . to the end of $path_map because that's what we're appending
     * to the $looked_up string when we successfully find the object
     */
    if($looked_up === "$path_map.") return $mutant;
    else if($throw_on_fail == "warn") \trigger_error("Could not find `$path_map`");
    else if($throw_on_fail === true) throw new Exception("Could not look up `$path_map`");
    else return; // Return undefined
}

/** Give this function a string and it will parse it as Markdown. $untrusted tells markdown to 
 * sanitize any HTML or links in the the parsing process.
 */
function from_markdown($string,$untrusted = true){
    $md = new Parsedown();
    $md->setSafeMode($untrusted);
    return $md->text($string);
}

function mongo_cursor($collection,$database = null){
    if(!$database) $database = app('database');
    try{
        $client = new MongoDB\Client(app('server_address'));
    } catch(Exception $e){
        die("Cannot connect to database");
    }
    $database = $client->{$database};
    return $database->{$collection};
}

function random_string($length,$string = null){
    $validChars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $min = 0;
    $max = strlen($validChars) - 1;
    $random = "";
    for($i = 0; $i <= $length; $i++){
        $random .= $validChars[rand($min,$max)];
    }
    return $random;
}

/** ==============================================
 *  Cross-Site Request Forgery Mitigation Routines
 *  ============================================== 
 */

/** Returns a CSRF Token (basically, an encrypted password) that's been truncated */
function get_csrf_token(){
    return str_replace('$2y$10$',"",password_hash(csrf_session_token(), PASSWORD_BCRYPT));
}

/** Validate our supplied CSRF token 
 * @param string $token - A CSRF token generated by get_csrf_token()
*/
function validate_csrf_token($token){
    /** We get our raw CSRF token */
    $raw_text_seed = csrf_session_token();
    /** We set our token string back to a token */
    $password_string = '$2y$10$'.$token;
    /** Verify our password */
    return password_verify($raw_text_seed,$password_string);
}

/** Returns a raw CSRF token (unencrypted) */
function csrf_session_token(){
    if(key_exists('csrf_old_token',$_COOKIE)) return app('csrf_seed') . $_COOKIE['csrf_old_token'];
    // Add "was updated" check
    return app('csrf_seed') . $_COOKIE[app('session_cookie_name')];
}

/** Handle token expiration. This function should return the same value until a
 * set time has passed.
 */
function csrf_token_date(){
    /** TODO: TOKEN EXPIRATION */
    return floor(time(),18000);
}

/** Add a CSRF Token element to any template with @csrf_token(); */
function csrf_token(){
    return "<input type='hidden' name='csrf_token' value='".get_csrf_token()."'>";
}

/** Add a CSRF token as an attribute to any template with @csrf_attribute(); */
function csrf_attribute(){
    return "token=\"".get_csrf_token()."\"";
}

/** JSON */

function get_json($file_name,$array = true){
    return json_decode(file_get_contents($file_name),$array);
}

function jsonc_decode($json, $assoc = false, $depth = 512, $options = 0) {
    /** Remove // and multiline comments from JSON, then parse. */
    $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);

    return json_decode($json, $assoc, $depth, $options);
}

/** Build Object */

function build_array_from_path(&$arr,$path,$value,$delimiter = "."){
    $keys = explode($delimiter,$path);
    foreach($keys as $key){
        $arr = &$arr[$key];
    }
    $arr = $value;
}


function build_object_from_paths($object){
    $mutant = [];
    foreach($object as $path => $value){
        $arr = [];
        build_array_from_path($arr,$path,$value);
        $mutant = array_merge_recursive($mutant,$arr);
    }
    return $mutant;
}