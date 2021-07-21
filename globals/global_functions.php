<?php

/**
 * Global helper functions for the Cobalt Engine
 * 
 * The Cobalt Engine offers a variety of helpful functions that allow developers
 * more flexibility and handle many of the more tedious and oft-repeated tasks
 * that we've encountered while writing Cobalt. 
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

use Exceptions\HTTP\Confirm;

/** A shorthand way of getting a specific setting by providing the name of the 
 * setting as the only argument, calling this function without an argument will 
 * return all the settings.
 * 
 * @throws E_USER_ERROR If the setting was not found, an error will be thrown
 * @param string|null The name of the setting OR null
 * @return mixed The value of the setting
 */
function app($setting = null) {
    if (!defined("__APP_SETTINGS__")) {
        trigger_error("It's too early to be looking for settings. Returning NULL!", E_USER_WARNING);
        return null;
    }
    if ($setting === null) return __APP_SETTINGS__;
    if (key_exists($setting, __APP_SETTINGS__)) return __APP_SETTINGS__[$setting];
    try {
        lookup_js_notation($setting, __APP_SETTINGS__, true);
    } catch (Exception $e) {
        throw new Exception("Setting $setting does not exist");
    }
}

/** A getter function for accessing the current user's info.
 * 
 * Get the current user's information when called without arguments or specify
 * the name of the field you're trying to access.
 *  
 * @throws Exception 
 * @param string|null $info (Optional) The field name you're trying to access
 * @return mixed Session object, session property, or null if session does not 
 *               exist
 */
function session($info = null) {
    if (!isset($GLOBALS['session'])) return null;
    if ($info === null) return $GLOBALS['session'] ?? null;
    if (key_exists($info, $GLOBALS['session'])) return $GLOBALS['session'][$info];
    throw new Exception("Field $info does not exist");
}

/**
 * Check if the session exists
 *
 * @return bool
 */
function session_exists() {
    if (isset($GLOBALS['session']) && $GLOBALS['session'] === null) return false;
    if (isset($GLOBALS['session']) && !empty($GLOBALS['session']['pword'])) return true;
    return false;
}

/**
 * Check if the current user has permission.
 *
 * @throws \Exceptions\HTTP\Unauthorized if not logged in
 * @throws Exception if the permission specified does not exist
 * @param  string $perm_name the name of the permission to check for
 * @param  string|array $group the group name or list of group names. 
 *                      Can be null.
 * @return bool true if the user has permission, false otherwise
 */
function has_permission($perm_name, $group = null, $user = null, $throw_no_session = true) {
    return $GLOBALS['auth']->has_permission($perm_name, $group, $user, $throw_no_session);
}

/** This function will return a merged array of decoded JSON files that are 
 * found to exist. Later elements in the $paths argument will overwrite earlier 
 * elements of the same name.
 * 
 * If $merged is false, the decoded files will be returned as separate elements 
 * of the array.
 */
function get_all_where_available($paths, $merged = true) {
    $available = [];
    foreach ($paths as $key => $path) {
        if (file_exists($path)) $available[$key] = jsonc_decode(file_get_contents($path), true);
    }
    if ($merged) return array_merge(...$available);
    return $available;
}

/** Hand this function an array of files that might exist and this function will
 *  return an array of file paths that exist */
function files_exist_the_hard_way($arr, $error_on_empty = true) {
    $result = [];
    foreach ($arr as $file) {
        if (file_exists($file)) array_push($result, $file);
    }
    if ($error_on_empty && empty($result)) throw new Exception(__FUNCTION__ . " requires that it find at least one file that exists.");
    return $result;
}

/** Hand this function an array of files that might exist and this function will
 * return an array of file paths that exist. If TRUE is used as the second 
 * argument and no files are found, an exception will be thrown.
 */
function files_exist($arr, $error_on_empty = true) {
    $values = array_values(array_filter($arr, "file_exists"));
    if ($error_on_empty && empty($values)) throw new Exception(__FUNCTION__ . " requires that it find at least one file that exists.");
    return $values;
}

/**
 * Searches for filename in given directory list.
 * 
 * Loops through an array of directories and looks for the filename inside them.
 * @param array $arr_of_paths A list of directories to search for $filename
 * @param string $filename The name of the file to find
 * @return string|false false if no file found, path name as string otherwise
 */
function find_one_file(array $arr_of_paths, $filename) {
    foreach ($arr_of_paths as $path) {
        $file = "$path/$filename";
        if (file_exists($file)) return $file;
    }
    return false;
}

/** Checks if non-false is returned by find_one_file and returns true, otherwise
 * returns false
 * @param string $template path relative to template dirs
 * @return bool
 */
function template_exists($template) {
    $file = find_one_file($GLOBALS['TEMPLATE_PATHS'], $template);
    if ($file !== false) return true;
    return false;
}

$GLOBALS['CLASSES_DIR'] = [
    __APP_ROOT__ . "/private/classes/",
    __ENV_ROOT__ . "/classes/"
];

/** The autoload routine for our classes.
 * @throws Exception if $class could not be loaded
 * @todo do we *want* this class to 
 * @param string $class the class name
 */
function cobalt_autoload($class) {
    $namespace_to_path = str_replace("\\", "/", $class) . ".php";

    $file = find_one_file($GLOBALS['CLASSES_DIR'], $namespace_to_path) ?? "";

    if ($file !== false) {
        require_once $file;
        return;
    }

    // Load class databases
    if (!isset($GLOBALS['class_directory'])) $GLOBALS['class_directory'] = get_all_where_available([__ENV_ROOT__ . '/classes/class_directory.json', __APP_ROOT__ . '/private/classes/class_directory.json']);
    $load = null;
    // Check if the class we're trying to load exists in the classes property
    if (key_exists($class, $GLOBALS['class_directory']['classes'])) {
        $load = $GLOBALS['class_directory']['classes'][$class];
    }
    if ($class[0] === "\\") $class = substr($class, 1);
    $explode = explode("\\", $class);
    $match_pattern = "/(.*)\\(\w+$)/";
    if (count($explode) === 2) {
        $namespace = $explode[0];
        $class_name = $explode[1];
        if (key_exists($namespace, $GLOBALS['class_directory']['namespaces'])) {
            $load = $GLOBALS['class_directory']['namespaces'][$namespace];
        }
    }

    // Throw an error if we don't have a load candidate
    if ($load === null) throw new Exception("Could not load $class");

    // If the path key exists, process the strings and require the file
    if (key_exists('path', $load)) {
        $final_name = str_replace(
            ['__ENV_CLASSES__', '__APP_CLASSES__'],
            [__ENV_ROOT__ . "/classes/", __APP_ROOT__, "/private/classes/"],
            $load['path']
        );
        if (pathinfo($final_name, PATHINFO_EXTENSION) !== "php") $final_name .= "$class_name.php";
        require_once $final_name;
        return;
    }
}

/** Updates @global WEB_PROCESSOR_TEMPLATE with the parameter's value
 * @deprecated use new *set_template("/path/to/template.html")*
 * @param string $path The path name relative to TEMPLATE_PATHS
 * @return void
 */
function add_template($path) {
    set_template($path);
}

/** Updates @global WEB_PROCESSOR_TEMPLATE with the parameter's value
 * @param string $path The path name relative to TEMPLATE_PATHS
 * @return void
 */
function set_template($path) {
    $GLOBALS['web_processor_template'] = $path;
}

/** Creates @global WEB_PROCESSOR_VARS or merges param into WEB_PROCESSOR_VARS.
 * 
 * A few template vars for quick reference:
 * title      - The title of the page
 * main_id    - the main element's id
 * body_id    - the body element's id
 * body_class - the body element's class list
 * 
 * @param array $vars MUST BE ASSOCIATIVE ARRAY
 * @return void
 */
function add_vars($vars) {

    if (!isset($GLOBALS['WEB_PROCESSOR_VARS'])) {
        $GLOBALS['WEB_PROCESSOR_VARS'] = $vars;
        return;
    }

    $GLOBALS['WEB_PROCESSOR_VARS'] = array_merge($GLOBALS['WEB_PROCESSOR_VARS'], $vars);
}

$GLOBALS['TEMPLATE_BINDINGS'] = [
    "html_head_binding", "noscript_binding_after", "header_binding_before",
    "header_binding_middle", "header_binding_after", "main_content_binding_before",
    "main_content_binding_after", "footer_binding_before", "footer_binding_after"
];

/**
 * Append a value to a particular template binding
 * 
 * Valid bindings: html_head_binding, noscript_binding_after, header_binding_before, 
 * header_binding_middle, header_binding_after, main_content_binding_before, 
 * main_content_binding_after, footer_binding_before, footer_binding_after
 * 
 * @param string $binding_name the name of the binding
 * @param string $value the value to be bound
 * @return void
 */
function bind($binding_name, $value) {


    if (!in_array($binding_name, $GLOBALS['TEMPLATE_BINDINGS'])) throw new Exception("Invalid binding");

    if (!isset($GLOBALS['WEB_PROCESSOR_VARS'][$binding_name]))
        $GLOBALS['WEB_PROCESSOR_VARS'][$binding_name] = $value;
    else $GLOBALS['WEB_PROCESSOR_VARS'][$binding_name] .= $value;
}

/** 
 * This function accepts a JS object notated $path_map and searches $vars for a 
 * value which matches $path_map
 *  $path_map = "map.get.item"
 *  $vars = ['map' => ['get' => ['item' => 'value']]]
 *  "value" // Result
 * 
 *  $path_map requires a string and should be in "dot.notation" format
 *  $vars can be an array or object
 *  $throw_on_fail should be set to false, true, or "warn"
 */
function lookup_js_notation(String $path_map, $vars, $throw_on_fail = false) {
    $mutant = $vars;
    $separated = explode(".", $path_map);
    $looked_up = "";

    foreach ($separated as $key) {
        $type = gettype($mutant); // Get the type of the mutant
        /** If we have updated the mutant this iteration, $break will be set 
         * to false. */
        $break = true;

        /** If it's an array, we'll check if the key exists and set the value
         * of $mutant to the found value */
        if ($type === "array") {
            if (!key_exists($key, $mutant)) break; // Break if we can't find the key
            $mutant = $mutant[$key];
            $break = false;
        }

        /** If it's an object, we'll check if the key exists and set the value
         * of $mutant to the found property */
        if ($type === "object") {
            if (is_a($mutant, "\Validation\Normalize")) {
                $temp_path = get_temp_path($path_map, $key);
                if (isset($mutant->{$temp_path})) $mutant = $mutant->{$temp_path};
                return $mutant;
            }
            if (!isset($mutant->{$key})) break; // Break if we can't find the property
            $mutant = $mutant->{$key};
            $break = false;
        }

        /** If we didn't update our mutant this iteration, then we need to break. */
        if ($break) break;

        /** Update the pathname so we can check if we found the correct path. */
        $looked_up .= "$key.";
    }

    /** We're adding a . to the end of $path_map because that's what we're 
     * appending to the $looked_up string when we successfully find the object
     */
    if ($looked_up === "$path_map.") return $mutant;
    else if ($throw_on_fail == "warn") throw new Exception("Could not find `$path_map`");
    else if ($throw_on_fail === true) throw new Exception("Could not look up `$path_map`");
    else return; // Return undefined
}

function get_temp_path($path, $key) {
    $index = strpos($path, $key);
    $substr = substr($path, $index);
    return $substr;
}

/** Give this function a string and it will parse it as Markdown. $untrusted 
 * tells markdown to 
 * sanitize any HTML or links in the the parsing process.
 */


/**
 * from_markdown
 *
 * @param  string $string - The string you wish to parse as markdown
 * @param  bool $untrusted - Whether the markdown is user input
 * @return string - HTML-formatted string
 */
function from_markdown(string $string, bool $untrusted = true) {
    $md = new Parsedown();
    $md->setSafeMode($untrusted);
    return $md->text($string);
}


/**
 * db_cursor
 * The
 * 
 * @param string $collection - The name of the collection
 * @param string $database - (Optional) The name of the database
 * @return object
 */
function db_cursor($collection, $database = null) {
    if (!$database) $database = app('database');
    try {
        $client = new MongoDB\Client(app('server_address'));
    } catch (Exception $e) {
        die("Cannot connect to database");
    }
    $database = $client->{$database};
    return $database->{$collection};
}

/**
 * random_string
 *
 * @param  int $length
 * @param  string $string
 * @return string Random string
 */
function random_string($length, $fromChars = null) {
    $validChars = $fromChars ?? "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $min = 0;
    $max = strlen($validChars) - 1;
    $random = "";
    for ($i = 0; $i <= $length; $i++) {
        $random .= $validChars[rand($min, $max)];
    }
    return $random;
}

/** ==============================================
 *  Cross-Site Request Forgery Mitigation Routines
 *  ============================================== 
 */

/** 
 * Returns a CSRF Token (basically, an encrypted password) that's been 
 * truncated 
 * @return string Encrypted CSRF Token
 * */
function get_csrf_token() {
    return str_replace('$2y$10$', "", password_hash(csrf_session_token(), PASSWORD_BCRYPT));
}

/** 
 * Validate our supplied CSRF token 
 * @throws Exception if no cookie is specified
 * @param string $token A CSRF token generated by get_csrf_token()
 */
function validate_csrf_token($token) {
    /** We get our raw CSRF token */
    $raw_text_seed = csrf_session_token();
    if ($raw_text_seed === "") throw new Exception("No cookie specified");
    /** We set our token string back to a token */
    $password_string = '$2y$10$' . $token;
    /** Verify our password */
    return password_verify($raw_text_seed, $password_string);
}

/** Returns a raw CSRF token (unencrypted)
 * @return string Unencrypted CSRF token
 */
function csrf_session_token() {
    if (key_exists('csrf_old_token', $_COOKIE)) return app('csrf_seed') . $_COOKIE['csrf_old_token'];
    // Add "was updated" check
    if (!isset($_COOKIE[app('session_cookie_name')])) return app('csrf_seed');
    return app('csrf_seed') . $_COOKIE[app('session_cookie_name')];
}

/** Handle token expiration. This function should return the same value until a
 * set time has passed.
 */
function csrf_token_date() {
    /** TODO: TOKEN EXPIRATION */
    return (string)round(time(), -5);
}

/** Add a CSRF Token element to any template with @csrf_token(); 
 * @return string  HTML hidden input named csrf_token with its value set to 
 *                 the result of get_csrf_token()
 */
function csrf_token() {
    return "<input type='hidden' name='csrf_token' value='" . get_csrf_token() . "'>";
}

/** Add a CSRF token as an attribute to any template with @csrf_attribute(); 
 * @return string - Token Attribute
 */
function csrf_attribute() {
    return "token=\"" . get_csrf_token() . "\"";
}

/** Load a file containing JSON and parse it 
 * @param string $file_name path to a JSON file
 * @param bool $array return the parsed JSON as an array rather than as an object
 * @return mixed
 */
function get_json($file_name, $array = true) {
    if (!file_exists($file_name)) {
        if ($array) return [];
        else return false;
    }
    $json = file_get_contents($file_name);
    return json_decode($json, $array);
}

/** Parse JSONC (commented JSON)
 * @param string $json the JSON string to be parsed
 * @param bool $assoc parse as an object (false) or array (true)
 * @param int $depth User specified recursion depth.
 * @param int $flags PHP JSON flags
 */
function jsonc_decode($json, $assoc = false, $depth = 512, $flags = 0) {
    /** Remove // and multiline comments from JSON, then parse. */
    $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);

    return json_decode($json, $assoc, $depth, $flags);
}

/** Used with the '...' route path symbol, provide the string as $path amd */
function build_array_from_path(&$arr, $path, $value, $delimiter = ".") {
    $keys = explode($delimiter, $path);
    foreach ($keys as $key) {
        $arr = &$arr[$key];
    }
    $arr = $value;
}


function build_object_from_paths($object) {
    $mutant = [];
    foreach ($object as $path => $value) {
        $arr = [];
        build_array_from_path($arr, $path, $value);
        $mutant = array_merge_recursive($mutant, $arr);
    }
    return $mutant;
}

function is_secure() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}

/** Used with the '...' route path symbol, provide the string as $path and valid
 * keys as $keys
 * 
 * If the path equals `/some/path/key/value` and $keys equals ['key']
 * 
 * The return value will be ['key' => 'value']
 * 
 * All other info in the string will be ignored.
 * 
 * @param string $path
 * @param array $keys a list of valid keys to parse for
 * @return array the processed associative array
 */
function associative_array_helper(string $path, array $keys) {
    $exploded = explode("/", $path);
    $array = array_fill_keys($keys, null);
    for ($i = 0; $i < count($exploded); $i++) {
        if (in_array($exploded[$i], $keys)) {
            $array[$exploded[$i]] = $exploded[$i + 1];
            $i++;
        }
    }
    return $array;
}

function associative_to_path(array $arr) {
    $path = "/";
    foreach ($arr as $name => $val) {
        $path .= "$name/$val/";
    }
    return $path;
}

/**
 * A shorthand way of rendering a template and getting the results. This is
 * included so you can include a template inside another template. This has the
 * potential to cause some recursive crap... so use caution!
 *
 * @param  string $template The name of the template
 * @param  mixed  $vars     Variables to include
 * @return string Processed template
 */
function with(string $template, $vars = []) {
    $render = new \Render\Render();
    if ($vars === []) $vars = $GLOBALS['WEB_PROCESSOR_VARS'];
    $render->set_vars($vars);
    $render->from_template($template);
    return $render->execute();
}

/** An error-tolerant template inclusion routine. Wraps the `with` function in a
 * try/catch block
 * 
 * @param string  $template The name of the template
 * @param mixed   $vars     Variables to include
 * @return string The processed template OR an empty string on error
 */
function maybe_with($template, $vars = []) {
    if (!$template) return "";
    if (!is_string($template)) return "";
    try {
        return with($template, $vars);
    } catch (Exception $e) {
        return "";
    }
}

function conditional_addition(string $template, bool $is_shown, $vars = []) {
    if (!$is_shown) return "";
    return with($template, $vars);
}

function with_each(string $template, $docs, $var_name = 'doc') {
    $rendered = "";
    foreach ($docs as $doc) {
        $rendered .= with($template, array_merge($GLOBALS['WEB_PROCESSOR_VARS'], [$var_name => $doc]));
    }
    return $rendered;
}

/** Compare two pathnames
 * 
 * $base_dir is used to substr $path after they have both been canonincalized.
 * If the two pathnames exactly match after this process, we know that $path is 
 * a descendant of $base_dir.
 * 
 * NOTE: This function returns null if either pathname cannot be canonincalized.
 * 
 * @param string $base_dir The dir we are checking against
 * @param string $path The path we want to see 
 * @return bool|null Returns null if unable to resolve canonincal pathname
 */
function is_child_dir($base_dir, $path) {
    // Check if files exist
    if (!file_exists($path) || !file_exists($base_dir)) return null;
    $base_dir = realpath($base_dir); // Canonicalize base dir
    $base_len = strlen($base_dir);
    // if($path && strlen($base_dir) < strlen($path))
    $substr = substr(realpath($path), 0, $base_len);
    return ($substr === $base_dir); // return comparison operation.
}

/** Create a directory listing from existing web GET routes
 * 
 * with_icon, prefix, classes, id
 * 
 * @param string $directory_group the name of the key
 */
function get_route_group($directory_group, $misc = []) {
    $misc = array_merge(['with_icon' => false, 'prefix' => "", 'classes' => "", 'id' => ""], $misc);
    if ($misc['with_icon']) $misc['classes'] .= " directory--icon-group";
    if ($misc['id']) $misc['id'] = "id='$misc[id]' ";
    if ($misc['classes']) $misc['classes'] = " $misc[classes]";
    $ul = "<ul $misc[id]" . "class='directory--group$misc[classes]'>";

    foreach ($GLOBALS['router']->routes['get'] as $route) {
        $groups = $route['navigation'] ?? false;
        if (!$groups) continue;
        // If we get here, we know we [probably] have an array

        // Now we check if the directory group is in $groups or the key exists
        // If both are FALSE, then we skip list assembly.
        if (!in_array($directory_group, $groups) && !key_exists($directory_group, $groups)) continue;
        if ($route['permission'] && !has_permission($route['permission'], null, null, false)) continue;

        $info = $groups[$directory_group] ?? $route['anchor'] ?? false;
        $ul .= build_directory_item($info, $misc['with_icon'], $misc['prefix']);
    }

    return $ul . "</ul>";
}

function build_directory_item($item, $icon = false, $prefix = "") {
    if ($icon) $icon = "<ion-icon name='$item[icon]'></ion-icon>";
    else $icon = "";
    $attributes = $item["attributes"] ?? '';
    if (!empty($prefix) && $prefix[strlen($prefix) - 1] == "/") $prefix = substr($prefix, 0, -1);
    return "<li><a href='$prefix$item[href]' $attributes>$icon" . "$item[name]</a></li>";
}

function get_schema_group_names(string $group_name, array $schema) {
    $elements = [];
    foreach ($schema as $field => $value) {
        if (isset($value['groups']) && in_array($group_name, $value['groups'])) $elements += [$field => $value];
    }
    return $elements;
}

function get_schema_group_elements($group_name, $schema) {
}

function schema_group_element($tag, $attributes, $label = "") {
    $closures = [
        'input' => "",
        'default' => "</$tag>"
    ];
    $attrs = "";
    foreach ($attributes as $key => $value) {
        if (is_callable(($value))) $value = $value($key, $attributes, $label);
        $attrs = " $key=\"" . htmlspecialchars($value) . "\"";
    }
    return "<$tag$attributes>";
}

/** Convert cents to dollars with decimal fomatting (not prepended by a "$" dollar sign)
 * @param int $cents 
 * @return string the dollar value as a string
 * */
function cents_to_dollars($cents) {
    $dollars = round($cents / 100, 2);
    return number_format($dollars, 2);
}

/** Convert a Mongo Date object to a formated date
 * @param object $date instance of MongoDB\BSON\UTCDateTime
 * @param string $fmt (optional) the format of the resulting date string
 *                - defaults to `<input type='date' value="Y/m/d">` expected format
 * @return string formated date
 */
function mongo_date($date, $fmt = "Y-m-d") {
    if (!$date) return "";
    $date = (string)$date / 1000;
    return date($fmt, $date);
}

/**  */
function date_instance($date) {
}

function phone_number_format($number, $format = "(ddd) ddd-dddd") {
    $num_index = 0;
    $num_max = strlen($number);
    $formatted = "";
    for ($i = 0; $i < strlen($format); $i++) {
        if ($format[$i] === "d") {
            if ($num_index >= $num_max) {
                $formatted .= "n";
                continue;
            }
            $formatted .= $number[$num_index];
            $num_index++;
        } else {
            $formatted .= $format[$i];
        }
    }
    return $formatted;
}

function phone_number_normalize($number) {
    // List of characters we don't want to store in our db
    $junk = ["(", ")", " ", "-", "."];

    // Strip the junk characters out of the string
    $value = str_replace($junk, "", $number);
    return $value;
}

function flex_table($docs, $table, $schema) {
    $result = [];
    $index = 1;
    foreach ($docs as $doc) {
        $event = new $schema($doc);
        $result[0] = "<flex-row>";
        $result[$index] = "<flex-row>";
        foreach ($table as $key => $cell) {
            $result[0] .= "<flex-header>$cell[header]</flex-header>";
            $result[$index] .= "<flex-cell>" . (isset($cell['display']) ? $cell['display']($event, $key) : $event->{$key}) . "</flex-cell>";
        }
        $result[0] .= "</flex-row>";
        $result[$index] .= "</flex-row>";
        $index++;
    }
    return "<flex-table>" . implode("", $result) . "</flex-table>";
}

/**
 * Check for confirmation headers and throw an exception if they don't exist
 * 
 * @param string $message confirmation message that the user will see
 * @param array $data data that the confirmation dialog will re-submit
 * @param string $okay the message to "continue"
 * @return bool true if headers exist 
 * @throws Confirm if headers are not detected throw Confirm
 */
function confirm($message, $data, $okay = "Continue", $dangerous = true) {
    $headers = apache_request_headers();
    if (key_exists('X-Confirm-Dangerous', $headers) && $headers['X-Confirm-Dangerous']) return true;
    throw new \Exceptions\HTTP\Confirm($message, $data, $okay, $dangerous);
}


function plugin($name) {
    if (isset($GLOBALS['ACTIVE_PLUGINS'][$name])) return $GLOBALS['ACTIVE_PLUGINS'][$name];
    throw new Exception('Plugin is not active!');
}

/**
 * 
 * @param iterator $results the results of a Mongo query
 * @param string $schema_name the name of the schema class
 * @return array every instance of the mongo query as a Cobalt schema
 */
function results_to_schema($results, string $schema_name): array {
    $array  = [];
    // if ($schema_name instanceof \Validation\Normalize === false) throw new Exception("$schema_name is not an instance of \Validation\Normalize");
    foreach ($results as $i => $doc) {
        $array[$i] = new $schema_name($doc);
    }
    return $array;
}
