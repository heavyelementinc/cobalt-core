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

use Cache\Manager;
use Cobalt\Customization\CustomSchema;
use Cobalt\Maps\Exceptions\LookupFailure;
use Cobalt\Maps\GenericMap;
use Cobalt\Pages\PageMap;
use Cobalt\Pages\PostMap;
use Cobalt\Renderer\Render;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Exceptions\HTTP\Error;
use Exceptions\HTTP\HTTPException;
use Exceptions\HTTP\NotFound;

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
        return lookup_js_notation($setting, __APP_SETTINGS__, true);
    } catch (Exception $e) {
        throw new Exception("Setting $setting does not exist");
    }
}

require_once __ENV_ROOT__ . "/globals/helpers/client.php";
require_once __ENV_ROOT__ . "/globals/helpers/cobalt.php";
require_once __ENV_ROOT__ . "/globals/helpers/numbers.php";
require_once __ENV_ROOT__ . "/globals/helpers/arrays.php";
require_once __ENV_ROOT__ . "/globals/helpers/strings.php";
require_once __ENV_ROOT__ . "/globals/helpers/requests.php";
require_once __ENV_ROOT__ . "/globals/helpers/routes.php";

/** The autoload routine for our classes.
 * @throws Exception if $class could not be loaded
 * @todo do we *want* this class to 
 * @param string $class the class name
 */
function cobalt_autoload($class) {
    global $CLASSES_DIR;
    $namespace_to_path = str_replace("\\", "/", $class) . ".php";
    
    $file = find_one_file($CLASSES_DIR, $namespace_to_path) ?? "";

    try {
        if ($file !== false) {
            try{
                require_once $file;
            } catch (ParseError $e) {
                kill("Syntax error in ".obfuscate_path_name($e->getFile()));
            }
            return;
        }
        $controllers_special_case = '/Controllers/';
        if (preg_match($controllers_special_case, $class)) {
            $file = find_one_file([__APP_ROOT__ . "/controllers", __ENV_ROOT__ . "/controllers"], $class);
            if ($file !== false) {
                require_once $file;

                return;
            }
        }

        $has_namespace = strpos("\\", $class);
        if($has_namespace === false) {
            $file = find_one_file([__APP_ROOT__ . "/controllers", __ENV_ROOT__ . "/controllers"], $class . ".php");
            if ($file !== false) {
                require_once $file;
                return;
            }
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
    } catch (ParseError $e) {
        print("<pre>");
        $file = $e->getFile() . ': ' . $e->getLine();
        if (app('debug')) {
            print("ParseError when loading $file");
            print("\n" . $e->getMessage());
        } else {
            print("A error was found. Please contact your system administrator with the following error code:\n");
            print(base64_encode($e->getMessage() . ' ' . $file));
        }
        exit;
    } catch (Exception $e) {
        print($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
        exit;
    } catch (Error $e) {
        print("<pre>");
        $file = $e->getFile() . ': ' . $e->getLine();
        if (app('debug')) {
            print("Fatal error when loading $file");
            print("\n" . $e->getMessage());
        } else {
            print("A error was found. Please contact your system administrator with the following error code:\n");
            print(base64_encode($e->getMessage() . ' ' . $file));
        }
        exit;
    }
}

/**
 * 
 * @param string $controllerName 
 * @param bool $instanced 
 * @param bool $path 
 * @return string|object
 * @throws HTTPException 
 */
function get_controller(string $controllerName, bool $instanced = false, bool $path = false) {
    $locations = [
        __APP_ROOT__ . "/controllers",
        __ENV_ROOT__ . "/controllers",
    ];

    $found = find_one_file($locations,"$controllerName.php");
    if($found === false) throw new HTTPException("Could not locate requested controller");
    if($path) return $found;
    require_once $found;
    if(!$instanced) return $controllerName;
    return new $controllerName();
}

/** Updates @global WEB_PROCESSOR_TEMPLATE with the parameter's value
 * @deprecated use *return view("/path/to/template.html")*
 * @param string $path The path name relative to TEMPLATE_PATHS
 * @return void
 */
function add_template($path) {
    return set_template($path);
}

/** Updates @global WEB_PROCESSOR_TEMPLATE with the parameter's value
 * @param string $path The path name relative to TEMPLATE_PATHS
 * @deprecated Setting a global template is deprecated behavior! Return a view() from your controller instead!
 * @return void
 */
function set_template($path, $vars = []) {
    try {
        global $TEMPLATE_PATHS;
        $templates = files_exist($TEMPLATE_PATHS);
    } catch (\Exception $e) {
        throw new NotFound("Template not found");
    }
    $GLOBALS['WEB_PROCESSOR_TEMPLATE'] = $path;
    return view($path, $vars);
}

/** Creates @global WEB_PROCESSOR_VARS or merges param into WEB_PROCESSOR_VARS.
 * 
 * A few template vars for quick reference:
 *  * title       - The title of the page
 *  * main_id     - the main element's id
 *  * body_id     - the body element's id
 *  * body_class  - the body element's class list
 *  * og_template - relative path of an open graph template
 * 
 * Prefix var names with `__` to export them to the client (the `__` is removed
 * so you don't have to reference them with the dunderscore)
 * 
 * @param array $vars MUST BE ASSOCIATIVE ARRAY
 * @return void
 */
function add_vars($vars):void {
    if(key_exists('custom', $vars)) throw new Exception("You may not override the `custom` var.");
    if (!isset($GLOBALS['WEB_PROCESSOR_VARS'])) $GLOBALS['WEB_PROCESSOR_VARS'] = [];
    $always_export_these_keys = ['body_id','body_class','main_id','main_class'];

    $exportable = ['body_id' => '','body_class' => '','main_id' => '','main_class' => ''];
    foreach($vars as $var => $val) {
        if(in_array($var, $always_export_these_keys)) $exportable += correct_exported_values($vars, $var, $val);
        if($var[0] . $var[1] == "__") $exportable += correct_exported_values($vars, $var, $val);
    }

    export_vars($exportable);

    $GLOBALS['WEB_PROCESSOR_VARS'] = array_merge($GLOBALS['WEB_PROCESSOR_VARS'], $vars);
}

/**
 * Set a single web processor var
 * @param string $name 
 * @param mixed $value 
 * @return void 
 * @throws Exception
 */
function set(string $name, mixed $value):void {
    add_vars([$name => $value]);
}

/**
 * Set a web processor variable as public
 * @param string $name 
 * @param mixed $value 
 * @return void 
 * @throws Exception 
 */
function export(string $name, mixed $value):void {
    $GLOBAL['EXPORTED_PUBLIC_VARS'][$name] = $value;
    set($name, $value);
}

function correct_exported_values(&$vars, $var, $val) {
    if($var[0].$var[1] !== "__") return [$var => $val];
    $correctedName = substr($var,2);
    $vars[$correctedName] = $val;
    unset($vars[$var]);
    return [$correctedName => $val];
}

function export_vars($vars) {
    $GLOBALS['EXPORTED_PUBLIC_VARS'] = array_merge($GLOBALS['EXPORTED_PUBLIC_VARS'], $vars);
}

function get_exportable_vars() {
    return $GLOBALS['EXPORTED_PUBLIC_VARS'];
}

function get_exportables_as_json($encode = 0) {
    if($encode === true) $encode = JSON_HEX_APOS;
    return base64_encode(json_encode($GLOBALS['EXPORTED_PUBLIC_VARS'], $encode | JSON_PRETTY_PRINT));
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
            $mutated_path = null;

            if (is_a($mutant, "\\Cobalt\\SchemaPrototypes\\SchemaResult")) {
                return $mutant;
            }

            if (is_a($mutant, "\\Cobalt\\Customization\\CustomizationManager")) {
                $mutant = $mutant->getCustomizationValue($key);
                $mutated_path = str_replace("custom.$key", "value", $path_map);
            }

            if(is_a($mutant, "\\Cobalt\\Maps\\GenericMap")) {
                $temp_path = get_temp_path($mutated_path ?? $path_map, $key);
                return lookup($temp_path, $mutant, $throw_on_fail);
                // if (isset($mutant->{$temp_path})) $mutant = $mutant->{$temp_path};
                // if (is_a($mutant, "\\Cobalt\\SchemaPrototypes\\MapResult")) return lookup_js_notation($temp_path, $mutant);
                // if($looked_up . "$temp_path" === $path_map) return $mutant;
            }

            if (is_a($mutant, "\Validation\Normalize")) {
                $temp_path = get_temp_path($mutated_path ?? $path_map, $key);
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

function get_custom(string $name):?CustomSchema {
    global $WEB_PROCESSOR_VARS;
    return $WEB_PROCESSOR_VARS['custom']->getCustomizationByUniqueName($name);
}

function lookup(string $name, mixed $subject, bool $throwOnFail = false): mixed {
    $type = is_array($subject) || $subject instanceof ArrayAccess;
    if($type) {
        if(isset($subject[$name])) return $subject[$name];
    }
    if ($subject instanceof MapResult) {
        $subject = $subject->getRaw();
    }
    if ($subject instanceof SchemaResult) {
        if(isset($subject->{$name})) return $subject->{$name};
        $type = "SchemaResult";
    }
    if(strpos($name, ".") >= 0) {
        $exploded = explode(".", $name);
        $first = array_shift($exploded);
        if($type === true && isset($subject[$first])) return lookup(implode(".", $exploded), $subject[$first]);
        if($type === "SchemaResult" && isset($subject->{$first})) return lookup(implode(".", $exploded), $subject->{$first});
        if($throwOnFail) throw new LookupFailure("Failed to find `$first` on " . gettype($subject));
        // return "";
    }
    if($subject instanceof GenericMap) {
        $schema = $subject->readSchema();
        if(key_exists($name, $schema)) return $subject->__toResult($name, null,  $schema[$name], $subject);
    }
    if($throwOnFail) throw new LookupFailure("Failed to find `$name` on " . gettype($subject));
    return "";
}

function get_temp_path($path, $key) {
    $index = strpos($path, $key);
    $substr = substr($path, $index);
    return $substr;
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
    return jsonc_decode($json, $array);
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


/**
 * A shorthand way of rendering a template and getting the results. This is
 * included so you can include a template inside another template. This has the
 * potential to cause some recursive crap... so use caution!
 *
 * @param  string $template The name of the template
 * @param  mixed  $vars     Variables to include
 * @return string Processed template
 * @deprecated Use view() instead
 */
function with(string $template, $vars = []) {
    return view($template, $vars);
}

/** An error-tolerant template inclusion routine. Wraps the `with` function in a
 * try/catch block
 * 
 * @param string  $template The name of the template
 * @param mixed   $vars     Variables to include
 * @return string The processed template OR an empty string on error
 * @deprecated use maybe_view()
 */
function maybe_with($template, $vars = []) {
    return maybe_view($template, $vars);
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
function view(string $template, array $vars = []):string {
    if(__APP_SETTINGS__['Render_use_v2_engine']) {
        $render = new Render();
        $render->setVars(array_merge($GLOBALS['WEB_PROCESSOR_VARS'], $vars));
        $render->getBodyFromTemplate($template);
    } else {
        $render = new \Render\Render();
        $vars = array_merge($GLOBALS['WEB_PROCESSOR_VARS'] ?? [], $vars);
        $render->set_vars($vars);
        $render->from_template($template);
    }
    return $render->execute();
}

function view_from_string(string $view, array $vars = []):string {
    $render = new \Render\Render();
    if ($vars === []) $vars = $GLOBALS['WEB_PROCESSOR_VARS'] ?? [];
    $render->set_vars($vars);
    $render->set_body($view, 'string');
    return $render->execute();
}

/** An error-tolerant template inclusion routine. Wraps the `with` function in a
 * try/catch block
 * 
 * @param string  $template The name of the template
 * @param mixed   $vars     Variables to include
 * @return string The processed template OR an empty string on error
 */
function maybe_view(string $template, array $vars = []):string {
    if (!$template) return "";
    if (!is_string($template)) return "";
    try {
        return view($template, $vars);
    } catch (Exception $e) {
        return "";
    }
}


function conditional_addition(string $template, bool $is_shown, $vars = []) {
    if (!$is_shown) return "";
    return view($template, $vars);
}

function with_each(string $template, $docs, $var_name = 'doc') {
    $rendered = "";
    foreach ($docs as $doc) {
        $rendered .= with($template, array_merge($GLOBALS['WEB_PROCESSOR_VARS'], [$var_name => $doc]));
    }
    return $rendered;
}

function view_each(string $template, Iterator|array $docs, string $var_name = 'doc', string|false $separator = "") {
    return implode($separator, view_array($template, $docs, $var_name));
}

function view_array(string $template, Iterator|array $docs, string $var_name = 'doc'){
    $array = [];
    $d = $docs;
    if(gettype($docs) === "array") {
        if(key_exists($var_name, $docs)) $d = $docs[$var_name];
    } else {
        $d = iterator_to_array($d);
    }
    foreach($d as $index => $doc){
        $array[$index] = view($template, array_merge(
            $d,
            [$var_name => $doc]
        ));
    }
    return $array;
}

function credit_card_form(array|object $data = [],$shipping = false):string {
    $currentYear = (int)date("Y");
    $years = "";
    for($i = 0; $i <= 10; $i++){
        $years .= "<option>" . $currentYear + $i . "</options>";
    }
    $months = '<option value="01">January (01)</option><option value="02">February (02)</option><option value="03">March (03)</option><option value="04">April (04)</option><option value="05">May (05)</option><option value="06">June (06)</option><option value="07">July (07)</option><option value="08">August (08)</option><option value="09">September (09)</option><option value="10">October (10)</option><option value="11">November (11)</option><option value="12">December (12)</option>';
    return view("/parts/credit-card.html",[
        'cc' => $data,
        'expiryYearOptions' => $years,
        'months' => $months,
        'shipping' => ($shipping) ? view('/parts/credit-card-shipping.html',['cc' => $data]) : "",
    ]);
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



function plugin($name) {
    if (isset($GLOBALS['ACTIVE_PLUGINS'][$name])) return $GLOBALS['ACTIVE_PLUGINS'][$name];
    throw new Exception('Plugin is not active!');
}

function get_posts_from_tags(array $tags, string $controller = "Posts", int $limit = 3):string {
    $html = "";
    /** @var \Controllers\Landing\Page */
    $postController = get_controller($controller, true);
    $posts = $postController->manager;
    
    $result = $posts->getPagesFromTags($tags, $limit);

    foreach($result as $post) {
        $html .= $postController->renderPreview($post);
        // view($view, [
        //     'post' => $post,
        //     'href' => $postController->path('post',[(string)$post['url_slug']])
        // ]);
    }

    return $html;
}

function get_related_posts(PageMap|array $relative_posts_or_tags, ?array $projection = null, string $controller = "Posts") {
    if(is_array($relative_posts_or_tags)) $tags = (new PageMap())->ingest(['tags' => $relative_posts_or_tags]);
    
    /** @var \Controllers\Landing\Page */
    $postController = get_controller($controller, true);
    $posts = $postController->manager;

    return $posts->getRelatedPages($tags, $projection);
}

function benchmark_start($name) {
    if(!__APP_SETTINGS__['enable_benchmark_profiling']) return;
    global $BENCHMARK_RESULTS;
    $BENCHMARK_RESULTS[$name] = [DB_BENCH_START => microtime(true) * 1000];
}

function benchmark_end($name) {
    if(!__APP_SETTINGS__['enable_benchmark_profiling']) return;
    global $BENCHMARK_RESULTS;
    $BENCHMARK_RESULTS[$name][DB_BENCH_END] = microtime(true) * 1000;
    $BENCHMARK_RESULTS[$name][DB_BENCH_DELTA] = $BENCHMARK_RESULTS[$name][DB_BENCH_END] - $BENCHMARK_RESULTS[$name][DB_BENCH_START];
    return $BENCHMARK_RESULTS[$name][DB_BENCH_DELTA];
}

function benchmark_reads() {
    global $BENCHMARK_RESULTS;
    $BENCHMARK_RESULTS[DB_BENCHMARK][DB_BENCH_READ] += 1;
}

function benchmark_writes($modified) {
    if($modified <= 0) return;
    global $BENCHMARK_RESULTS;
    $BENCHMARK_RESULTS[DB_BENCHMARK][DB_BENCH_WRITE] += 1;
}



function set_up_db_config_file(string $database, string $user, string $password, string $addr = "localhost", string $port = "27017", string $ssl = "false", string $sslFile = "", string $invalidCerts = "false", ?string $path = null) {
    $path = $path ?? $GLOBALS['db_config'];
    return file_put_contents($path,"<?php
/**
 * This is the bootstrap config file. We use this to
 * Set up our database access. This file is read every
 * time the app is instantiated.
 */

\$GLOBALS['CONFIG'] = [
    'db_driver'      => 'MongoDB', // The Cobalt Engine database driver to use to access the database (MongoDB is the only supported driver)
    'db_addr'        => '$addr', // The database's address
    'db_port'        => '$port', // The database port number
    'database'       => '$database', // The name of your app's database
    'db_usr'         => '$user', // The username for your database
    'db_pwd'         => '$password', // The password for your database
    'db_ssl'         => $ssl, // Enable SSL communication between the app and database
    'db_sslFile'     => '$sslFile', // The SSL cert file for communicating with the database
    'db_invalidCerts'=> $invalidCerts, // Allow self-signed certificates
];"
);
}






if(!function_exists("log_item")) {
    function log_item($message, $lvl = 1, $type = "grey", $back = "normal") {
        if ($lvl > $GLOBALS['cli_verbosity']) return;
        $date = date('Y-m-d');
        $logpath = __APP_ROOT__ . "/ignored/logs/";
        if(!is_dir($logpath)) mkdir($logpath, 0777, true);
        $resource = fopen($logpath . "cobalt-$date.log", "a");
        fwrite($resource, "[".date(DATE_RFC2822)."] {$message}\n");
        fclose($resource);
        if(!function_exists("say")) return;
        $m = fmt("[LOG $lvl]", 'i');
        $m .= " " . fmt($message, $type, $back);
        print($m . "\n");
    }
}





function set_crudable_flag(string $name, int $flag): int {
    global $CRUDABLE_CONFIG_TRACKER;
    if(!key_exists($name, $CRUDABLE_CONFIG_TRACKER)) $CRUDABLE_CONFIG_TRACKER[$name] = 0;
    $CRUDABLE_CONFIG_TRACKER[$name] += $flag;
    return $CRUDABLE_CONFIG_TRACKER[$name];
}

function get_crudable_flag(string $name): ?int {
    global $CRUDABLE_CONFIG_TRACKER;
    return $CRUDABLE_CONFIG_TRACKER[$name] ?? null;
}

function compare_and_juggle($canonical, $value) {
    if($canonical !== $value) $value = juggler(gettype($canonical), $value);
    return $value;
}

/**
 * Naievely convert between types
 * @param string $canonincal 
 * @param mixed $value 
 * @return mixed 
 * @throws TypeError if a resource is set as $value
 */
function juggler(string $canonincal, mixed $value) {
    switch($canonincal) {
        case "boolean":
            $value = (bool)$value;
            break;
        case "string":
            $value = (string)$value;
            break;
        case "integer":
            $value = (int)$value;
            break;
        case "double":
            $value = (double)$value;
            break;
        case "float":
            $value = (float)$value;
            break;
        case "array":
            $value = (array)$value;
            break;
        case "object":
            $value = (object)$value;
            break;
        case "resource":
            throw new TypeError("Cannot convert resources");
            break;
        case "NULL":
            $value = null;
            break;
    }
    return $value;
}

/**
 * @param string 
 * @return string|false Will return 
 */
function get_extension_from_file($file_path, $file_name = null, $trust_filename = false) {
    if($file_name && $trust_filename) return pathinfo($file_name, PATHINFO_EXTENSION);
    if(!file_exists($file_path)) return false;
    $ext = explode("/", mime_content_type($file_path));
    $type = $ext[0];
    $ext = $ext[1];
    if(substr($ext, 0, 2) == "x-") $ext = substr($ext, 2);
    // get_usable_mime_array();
    return match($ext) {
        "svg+xml" => "svg",
        "abiword" => "abw",
        "freearc" => "arc",
        "msvideo" => "avi",
        "vnd.amazon.ebook" => "azw",
        "octet-stream" => "bin",
        "bzip" => "bz",
        "bzip2" => "bz2",
        "cdf" => "cda",
        "msword" => "doc",
        "vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
        "vnd.ms-fontobject" => "eot",
        "epub+zip" => "epub",
        "gzip" => "gz",
        "vnd.microsoft.icon" => "ico",
        "java-archive" => "jar",
        "javascript" => "js",
        "ld+json" => "jsonld",
        "mpeg" => ($type == "audio") ? "mp3" : "mpeg",
        "vnd.apple.installer+xml" => "mpkg",
        "vnd.oasis.opendocument.presentation" => "opd",
        "vnd.oasis.opendocument.spreadsheet" => "ods",
        "vnd.oasis.opendocument.text" => "odt",
        "ogg" => ($type == "audio") ? "oga" : (($type == "video") ? "ogv" : "ogx"),
        "httpd-php" => "php",
        "vnd.ms-powerpoint" => "ppt",
        "vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
        "vnd.rar" => "rar",
        "mp2t" => "ts",
        "plain" => "txt",
        "xhtml+xml" => "xhtml",
        // "vnd.ms-excel" => "",
        default => $ext
    };
}

const USABLE_MIME_TYPE_CACHE_NAME = "mime_type.json";
const USABLE_MIME_TYPE_SOURCE_URL = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
const MIME_TYPE_CACHE_EXPIRES = 60 * 60 * 24 * 30;

function get_usable_mime_array(){
    $cacheMan = new Manager(USABLE_MIME_TYPE_CACHE_NAME);
    if ($cacheMan->cache_exists() && $cacheMan->modified() - time() < MIME_TYPE_CACHE_EXPIRES) return $cacheMan->get("json");
    $s = [];
    // Download and explode our file so we can loop through the lines
    foreach(@explode("\n",@file_get_contents(USABLE_MIME_TYPE_SOURCE_URL)) as $line) {
        $out = [];
        // If there is not first character set, skip
        if(!isset($line[0])) continue;
        // If the first character signifies a commented line, skip
        if($line[0] === "#") continue;
        // If preg_match_all returns false, skip
        if(preg_match_all('#([^\s]+)#', $line, $out) === false) continue;
        // If the regex didn't find a match, skip
        if(!isset($out[1])) continue;
        // Check how many items there are in the array. If it's less than or equal to one, skip
        if(($counted_items_in_array = count($out[1])) <= 1) continue;
        for($i=1; $i<$counted_items_in_array; $i++) {
            $s[$out[1][0]] = $out[1][$i];
        }
    }
    $cacheMan->set($s, true);
    return $s;
    // return @sort($s)?'$mime_types = array(<br />'.implode($s,',<br />').'<br />);':false;
}