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

use Cobalt\Customization\CustomSchema;
use Cobalt\Maps\Exceptions\LookupFailure;
use Cobalt\Maps\GenericMap;
use Cobalt\Posts\PostManager;
use Cobalt\Renderer\Render;
use Cobalt\SchemaPrototypes\SchemaResult;
use Controllers\CRUDController;
use Demyanovs\PHPHighlight\Highlighter;
use Drivers\UTCDateTime as DriversUTCDateTime;
use Exceptions\HTTP\Confirm;
use Exceptions\HTTP\Error;
use Exceptions\HTTP\HTTPException;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Reauthorize;
use Exceptions\HTTP\Unauthorized;
use Handlers\ApiHandler;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONArray;
use Validation\Exceptions\NoValue;
use Validation\Exceptions\ValidationIssue;

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
    if (key_exists($info, $GLOBALS['session']['__dataset'])) return $GLOBALS['session']['__dataset'][$info];
    return lookup_js_notation($info, $GLOBALS['session'], true);
    throw new Exception("Field $info does not exist");
}

function session_refresh() {
    $GLOBALS['auth'] = new \Auth\Authentication();
}

/**
 * Check if the session exists
 *
 * @return bool
 */
function session_exists() {
    if (isset($GLOBALS['session']) && $GLOBALS['session'] === null) return false;
    if (isset($GLOBALS['session'])) return true;
    return false;
}


/**
 * @param MongoDB\BSON\Document|mixed $it The Mongo document to be converted
 * @return array returns an array representation of the document
 */
function doc_to_array($it): array {
    if (is_array($it)) return $it;
    $result = [];
    foreach ($it as $key => $value) {
        if ($value instanceof \Traversable) {
            $result[$key] = doc_to_array($value);
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}

function iterator_to_array_recursive($it):array {
    $mutant = [];
    foreach($it as $key => $value) {
        if($value instanceof \Traversable) $value = iterator_to_array($value);
        if(is_array($value)) $mutant[$key] = iterator_to_array_recursive($value);
        else $mutant[$key] = $value;
    }
    return $mutant;
}

/**
 * Merges the elements of one or more arguments
 * @param array|Iterator $args,... Arguments
 * @return mixed 
 */
function merge() {
    $arguments = func_get_args();
    try {
        return array_merge(...$arguments);
    } catch (TypeError $e) {

    }
    $list = [];
    foreach($arguments as $i => $arg) {
        if($arg instanceof \MongoDB\Model\BSONDocument) {
            $list[$i] = doc_to_array($arg);
            continue;
        }
        if($arg instanceof Iterator) {
            $list[$i] = iterator_to_array($arg);
            continue;
        }
        $list[$i] = $arg;
    }
    return array_merge(...$list);
}

function array_append(&$array) {
    
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

/**
 * Checks if the current user has root permission
 * @return bool
 */
function is_root() {
    $session = session();
    if(!$session) return false;
    if(!key_exists('groups',(array)$session)) return false;
    return in_array('root',(array)$session['groups']->getArrayCopy());
}

/** This function will return a merged array of decoded JSON files that are 
 * found to exist. Later elements in the $paths argument will overwrite earlier 
 * elements of the same name.
 * 
 * If $merged is false, the decoded files will be returned as separate elements 
 * of the array.
 */
function get_all_where_available($paths, $merged = true, $throwOnFail = false) {
    $available = [];
    foreach ($paths as $key => $path) {
        $options = 0;
        if($throwOnFail) $options = JSON_ERROR_SYNTAX;
        try {
            if (file_exists($path)) $available[$key] = jsonc_decode(file_get_contents($path), true, 512 ,$options);
        } catch (Exception $e) {
            throw new Exception("Syntax error in `" . str_replace([__APP_ROOT__, __ENV_ROOT__],[""], $path) . '`');
        }
        if(!isset($available[$key])) continue;
        if($available[$key] === null || $available[$key] === []) unset($available[$key]);
    }
    if ($merged) return array_merge(...$available);
    return $available;
}

// function scan_dir_all(array $paths, $contexts = [__ENV_ROOT__, __APP_ROOT__]):array {
//     $results = [];
//     foreach($paths as $path) {
        
//         $dir = scandir($path);
//         $r = [];
//         foreach($dir as $d) {
            
//         }

//     }
//     return array_unique(array_merge(...$results));
// }

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
    $deprecated_path = __APP_ROOT__ . "/private";
    foreach ($arr_of_paths as $path) {
        $file = "$path/$filename";
        if (file_exists($file)) {
            if(substr($deprecated_path,0,strlen($deprecated_path)) === $deprecated_path) {
                // trigger_error("Your application's file structure is using the deprecated /private directory. Please move all your classes, templates, controllers, and routes to __APP_ROOT__", E_USER_DEPRECATED);
            }
            return $file;
        }
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

/**
 * Uses the controller's `title` to generate an ID. This is automatically applied
 * to a page if no `main_id` is specified.
 * 
 * This function does not guarantee the ID generated is unique in your DOM and
 * two pages with the same .
 * 
 * @return string[]|string|null 
 */
function get_main_id($prepend = true) {
    if (!isset($GLOBALS['WEB_PROCESSOR_VARS']['title'])) return "main-cobalt";
    $final = str_to_id($GLOBALS['WEB_PROCESSOR_VARS']['title']);
    if ($prepend) return "main-$final";
    return $final;
}

function str_to_id($str) {
    $replace = preg_replace("/([^\w])/", "-", $str);
    return strtolower(preg_replace("/(-{2,})/", "-", $replace));
}

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

function obfuscate_path_name(string $path_name):string {
    return str_replace([__ENV_ROOT__, __APP_ROOT__], ['__ENV_ROOT__', '__APP_ROOT__'], $path_name);
}

function get_controller($controllerName, $instanced = false, $path = false) {
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
 * @deprecated use new *set_template("/path/to/template.html")*
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
 * @param array $vars MUST BE ASSOCIATIVE ARRAY
 * @return void
 */
function add_vars($vars) {
    if(key_exists('custom', $vars)) throw new Exception("You may not override the `custom` var.");
    if (!isset($GLOBALS['WEB_PROCESSOR_VARS'])) {
        $GLOBALS['WEB_PROCESSOR_VARS'] = $vars;
        return;
    }

    $GLOBALS['WEB_PROCESSOR_VARS'] = array_merge($GLOBALS['WEB_PROCESSOR_VARS'], $vars);
}

function correct_exported_values(&$vars, $var, $val) {
    $correctedName = substr($var,2);
    $vars[$correctedName] = $val;
    unset($vars[$var]);
    return [$correctedName => $val];
}

$GLOBALS['EXPORTED_PUBLIC_VARS'] = [];

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

$GLOBALS['TEMPLATE_BINDINGS'] = [
    "html_head_binding", "noscript_binding_after", "header_binding_before",
    "header_binding_middle", "header_binding_after", "main_content_binding_before",
    "main_content_binding_after", "footer_binding_before", "footer_binding_after"
];

function set($name, $value) {
    add_vars([$name => $value]);
    return "";
}

function export($name,$value) {
    // $GLOBAL['EXPORTED_PUBLIC_VARS'][$name] = $value;
    return set($name,$value);
}

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
function from_markdown(?string $string, bool $untrusted = true) {
    if(!$string) return "";

    // [$string, $placeholders, $replacements] = parse_embeds($string);

    $md = new ParsedownExtra();
    $md->setSafeMode($untrusted);
    // $md->setMarkupEscaped($untrusted);
    $parsed = $md->text($string);

    $parsed = embed_from_img_tags($parsed);
    // $ytMatch = ["/&lt;img.*src=['\"].*(youtube).*v=[a-zA-Z0-9.*['\"].*&gt;/", "/<img.*src=['\"].*(youtube).*['\"].*>/"];

    // foreach($ytMatch as $url) {
    //     $matches = [];
    //     preg_replace($url, $parsed, $matches);

    //     $parsed = str_replace($match[0], , $parsed);
    // }

    // Implmentented reddit's ^ for superscript. Only works one word at a time.
    return preg_replace(
        [
            "/&lt;sup&gt;(.*)&lt;\/sup&gt;/",
            "/\^(\w)/",
            
            // "/<img src=['\"]()['\"])/"
            // "/&lt;a(\s*[='\(\)]*.*)&gt;(.*)&lt;\/a&gt;/",
        ],
        [
            "<sup>$1</sup>",
            "<sup>$1</sup>",

            // "<a$1>$2</a>",
        ],
        $parsed
    );
}

function youtube_embedder(DOMElement $img, DOMDocument $doc){
    $url = $img->getAttribute('src');
    $rawParams = parse_url($url, PHP_URL_QUERY);
    $host = parse_url($url, PHP_URL_HOST);
    $id = "";
    if($rawParams) {
        $params = [];
        parse_str($rawParams, $params);
        $id = $params['v'];
    } else {
        switch($host) {
            case "www.youtu.be":
            case "youtu.be":
                $id = parse_url($url, PHP_URL_PATH);
                if($id[0] == "/") $id = substr($id, 1);
                break;
        }
    }
    $figure = new DOMElement('figure');
    $doc->appendChild($figure);
    $iframe = new DOMElement('iframe');
    $figure->appendChild($iframe);

    $figure->setAttribute('class', 'content-embed content--youtube');

    $iframe->setAttribute('width', '560');
    $iframe->setAttribute('height', '315');
    $iframe->setAttribute('src', 'https://www.youtube.com/embed/'.$id);
    $iframe->setAttribute('title', 'YouTube video player');
    $iframe->setAttribute('frameborder', "0");
    $iframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
    $iframe->setAttribute('allowfullscreen', 'allowfullscreen');
    $img->replaceWith($figure);
    
}

function instagram_embedder(DOMElement $img, DOMDocument $dom) {
    $src = $img->getAttribute("src");
    $figure = new DOMElement('figure');
    $dom->appendChild($figure);
    $iframe = new DOMElement('ig-embed');
    $figure->appendChild($iframe);
    $figure->setAttribute('class', 'content-embed content--instagram');
    $iframe->setAttribute('src', $src); //"https://www.instagram.com/p/$src/?utm_source=ig_embed&amp;utm_campaign=loading");
    $img->replaceWith($figure);
}

function embed_from_img_tags($html) {
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $imgTags = $dom->getElementsByTagName("img");
    foreach($imgTags as $img) {
        $src = $img->getAttribute('src');
        $host = parse_url($src, PHP_URL_HOST);
        switch($host) {
            case "www.youtube.com":
            case "youtu.be":
            case "www.youtu.be":
            case "youtube.com":
                youtube_embedder($img, $dom);
                break;
            case "instagram.com":
            case "www.instagram.com":
                instagram_embedder($img, $dom);
                break;
        }
    }
    return $dom->saveHTML();
}

function markdown_to_plaintext(?string $string, $stripWhitespace = false) {
    $md = from_markdown($string);
    $md = strip_tags($md);
    if($stripWhitespace) $md = preg_replace("/[\s]/", " ", $md);
    $md = str_replace("\n", "\n\n", $md);
    return trim($md);
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
    
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match('/^https/',$_SERVER['HTTP_ORIGIN'] ?? "")) return true;
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
 * Will determine if an array has string keys
 * Will provide a false positive if indexes are non-linear
 * @param mixed $array 
 * @return bool 
 */
function is_associative_array(mixed $array) {
    if(gettype($array) !== "array") return false;
    if (array() === $array) return false;
    return array_keys($array) !== range(0, count($array) - 1);
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

function url_fragment_sanitize(string $value):string {
    $mutant = strtolower($value);
    // Remove any character that isn't alphanumerical and replace it with a dash
    $mutant = preg_replace("/([^a-z0-9])/", "-", $mutant);
    // Remove any consecutive dash
    $mutant = preg_replace("/(-){2,}/", "", $mutant);

    if (!$mutant || $mutant === "-") throw new ValidationIssue("\"$value\" is not suitable to transform into a URL fragment");

    return $mutant;
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

// function get_route_data(string $class, string $method, ?string $routeMethod = "get", string $context = null) {
    // global $ROUTER;
//     if($context === null) $context = "web";
//     $controllerAlias = "$class@$method";
//     $router = $ROUTER;
//     if(key_exists($controllerAlias, $GLOBALS['ROUTE_LOOKUP_CACHE'])) return route_replacement($GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias], $args, []);
//     // if($context !== $router->route_context) {
//     //     if(isset($GLOBALS['api_router'])) $router = $GLOBALS['api_router'];
//     //     if($context !== $router->route_context) throw new Error("Could not establish proper context");
//     // }
//     // $routes = $router->routes[$context][$routeMethod];
//     $route = null;
//     foreach($router->routes as $routes) {
//         foreach($routes[$routeMethod] as $r => $data) {
//             if($data['controller'] !== $controllerAlias) continue;
//             $GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias] = $data['real_path'];
//             return $data;
//         }
//     }
// }

/**
 * Limitations: this will only return the first route that uses the specified controller
 * @param string $class
 * @param string $method
 * @param array $args 
 * @param mixed $args 
 * 
 * @return string 
 */
function get_path_from_route(string $class, string $method, array $args = [], ?string $routeMethod = "get", string $context = null) {
    global $ROUTER;
    if($context === null) $context = "web";
    $controllerAlias = "$class@$method";
    if(key_exists($controllerAlias, $GLOBALS['ROUTE_LOOKUP_CACHE'])) return route_replacement($GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias], $args, []);
    // if($context !== $router->route_context) {
    //     if(isset($GLOBALS['api_router'])) $router = $GLOBALS['api_router'];
    //     if($context !== $router->route_context) throw new Error("Could not establish proper context");
    // }
    // $routes = $router->routes[$context][$routeMethod];
    $route = null;
    foreach($ROUTER->routes as $routes) {
        foreach($routes[$routeMethod] as $r => $data) {
            if($data['controller'] !== $controllerAlias) continue;
            $GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias] = $data['real_path'];
            return route_replacement($data['real_path'], $args, $data);
        }
    }

    $GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias] = $route;
    return $route;
}

function route_replacement($path, $args, $data = []) {
    $rt = $path;
    $regex = "/(\{{1}[a-zA-Z0-9]*\}{1}\??)/";
    
    $replacement = [];
    preg_match_all($regex,$rt,$replacement);

    $mutant = $rt;
    // if(gettype($replacement[0]) !== "array") $replacement[0] = [$replacement[0]];
    foreach($replacement[0] as $i => $replace) {
        $mutant = str_replace($replace, $args[$i] ?? $args[0] ?? "", $mutant);
    }

    return preg_replace("/\/{2,}/","/", $mutant);
}

/**
 * This will only return the first route that uses $directiveName
 * @param string $directiveName the "Controller@method" direvitve specified in your router table
 * @param array $args Any arguments used here will get filled in as values for {variables} in route names from left to right
 * @param array $context The context to search ("web", "admin", "apiv1", etc.)
 * @return string 
 * @throws Exception 
 */
function route(string $directiveName, array $args = [], array $context = []):string {
    $routeMethod = $context['method'] ?? "get";
    $ctx = $context['context'] ?? "web";
    $split = explode("@", $directiveName);
    
    $route = get_path_from_route($split[0], $split[1], $args, $routeMethod, $ctx);
    if(!$route) {
        $flag = get_crudable_flag($split[0]);
        if($flag === null) throw new Exception("Could not find route based on directive name.");
        if($flag !== CRUDABLE_CONFIG_ADMIN + CRUDABLE_CONFIG_APIV1) throw new Exception("Crudable has not been configured");
        throw new Exception("Could not find route based on directive name.");
    }
    return $route;
}

function validate_route($directiveName, $context) {
    global $ROUTER;
    $routeMethod = $context['method'] ?? "get";
    $ctx = $context['context'] ?? "web";
    
    $routes = $ROUTER->routes[$ctx][$routeMethod];

    foreach($routes as $r => $data) {
        if($data['controller'] !== $directiveName) continue;
        return true;
    }

    return false;
}

// TODO: Fix this
/** Create a directory listing from existing web GET routes
 * 
 * with_icon, prefix, classes, id, (array) ulPrefix, (array) ulSuffix, (bool) excludeWrapper
 * 
 * @param string $directory_group the name of the key
 */
function get_route_group_old($directory_group, $misc = []) {
    global $ROUTER;
    $misc = array_merge(['with_icon' => false, 'ulPrefix' => "", 'excludeWrapper' => false, 'classes' => "", 'id' => ""], $misc);
    if ($misc['with_icon']) $misc['classes'] .= " directory--icon-group";
    if ($misc['id']) $misc['id'] = "id='$misc[id]' ";
    if ($misc['classes']) $misc['classes'] = " $misc[classes]";
    
    // Check if we have prefixes or suffixes specified
    
    $ul = "<ul $misc[id]" . "class='directory--group$misc[classes]'>";
    if($misc['excludeWrapper'] === true) $ul = "";
    $current_route = $ROUTER->current_route;
    $list = $ROUTER->routes;

    // handleAuxiliaryRoutes($list, $misc, $directory_group);

    $group_to_process = [];

    foreach($list as $context => $methods) {
        foreach($methods as $method => $routes) {
            $nat_order = -1;
            foreach ($routes as $r => $route) {
                $groups = $route['navigation'] ?? false;
                if (!$groups) continue;
                // Now we check if the directory group is in $groups or the key exists
                // If both are FALSE, then we skip list assembly.
                if (!in_array($directory_group, $groups) && !key_exists($directory_group, $groups)) continue;
                if ($route['permission'] && !has_permission($route['permission'], null, null, false)) continue;
                $nat_order++;
                $group_to_process[] = [...$route, ...['r' => $r, 'context' => $context, 'current_nav_group' => $directory_group, 'nat_order' => $nat_order]];
            }
        }
    }

    uasort($group_to_process, function ($a, $b) {
        $order_a = $a['anchor']['order'] ?? $a['navigation'][$a['current_nav_group']]['order'] ?? $a['nat_order'];
        $order_b = $b['anchor']['order'] ?? $b['navigation'][$b['current_nav_group']]['order'] ?? $b['nat_order'];
        return $order_a - $order_b;
    });

    foreach($group_to_process as $key => $route) {
        $info = $groups[$directory_group] ?? $route['anchor'] ?? [];
        if(key_exists('unread',$route)) $info['unread'] = $route['unread'];
        if(!isset($info['name']) && isset($route['anchor'])) $info = array_merge($route['anchor'], $info);
        if ($route['r'] === $current_route) $info['attributes'] = 'class="current--route"';
        $ul .= build_directory_item($info, $misc['with_icon'], $route['context']);
    }

    $wrapper = ($misc['excludeWrapper']) ? "" : "</ul>";
    return $ul . $wrapper;
}

function get_route_group($directory_group, $misc = []) {
    global $ROUTER;
    $misc = array_merge(['with_icon' => false, 'ulPrefix' => "", 'excludeWrapper' => false, 'classes' => "", 'id' => ""], $misc);
    $rtGrp = new \Routes\RouteGroup($directory_group, $ROUTER->current_route ?? "",$misc['with_icon']);
    $rtGrp->setID($misc['id']);
    $rtGrp->setClassesFromString($misc['classes']);
    $rtGrp->setExcludeWrappers($misc['excludeWrapper']);
    return $rtGrp->render();
}

// TODO: Fix this
function handleAuxiliaryRoutes(&$list, $misc, $group):void {
    $prefix = ($misc['ulPrefix']) ? $misc['ulPrefix'] : [];
    $suffix = ($misc['ulSuffix']) ? $misc['ulSuffix'] : [];
    // If the prefixes or suffixes are strings, make them arrays
    if(gettype($prefix) === "string") $prefix = [$prefix];
    if(gettype($suffix) === "string") $suffix = [$suffix];
    $mutantPrefix = [];
    foreach($prefix as $pfx) {
        $mutantPrefix += auxRouteHandler($pfx, $group);
    }

    $mutantSuffix = [];

    foreach($suffix as $sfx) {
        array_push($mutantSuffix, [$sfx => auxRouteHandler($sfx, $group)]);
    }

    foreach($list as $element) {
        array_unshift($element['get'], ...$mutantPrefix);
        array_push($element['get'], ...$mutantSuffix);
    }
}

// TODO: Fix this
function auxRouteHandler($route, $group) {
    if(is_string($route)) {
        if(strpos($route,"@") !== false) {
            $rt = route($route);
            $rt['groups'] === [$group];
            return $rt;
        } 
        return [
            "/" . preg_quote($route) . "/" => [
                'original_path' => $route,
                'controller' => "",
                'anchor' => [
                    'label' => $route,
                    'href' => $route
                ],
                'groups' => [$group]
            ]
        ];
    } else if (is_array($route)) return route(...array_values($route));
    throw new Exception("Provided auxiliary is not a valid auxiliary route type");
}

function build_directory_item($item, $icon = false, $context = "") {
    $prefix = "";
    $icon = "";
    if ($icon) $icon = "<i name='$item[icon]'></i>";
    $attributes = $item["attributes"] ?? '';
    if ($context !== "web") {
        $prefix = app('context_prefixes')[$context]['prefix'];
        if($prefix[strlen($prefix) - 1] == "/") $prefix = substr($prefix, 0, -1);
    }
    $submenu = "";
    if (isset($item['submenu_group'])) $submenu = get_route_group($item['submenu_group'], ['classes' => 'directory--submenu', 'icon' => $icon, 'prefix' => $prefix]);
    if(strpos($submenu,'current--route')) {
        $current_route_classes = 'current--route current--route--parent';
        if(isset($item['attributes'])) {
            $items['attributes'] = substr($item['attributes'],-1) . "$current_route_classes\"";
        }
    }
    $unread = "";
    if (isset($item['unread']) && $item['unread'] instanceof \Closure) $unread_count = $item['unread']($item);
    if($unread_count) $unread = "<span class='unread'>$unread_count</span>";

    return "<li><a href='$prefix$item[href]' $attributes>$icon" . "$item[name]$unread</a>$submenu</li>";
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

/** Convert seconds to pretty string */
function prettify_seconds(?int $seconds) {
    if(!$seconds) return "";
    $date = new DateTime("00:00:00");
    $date->modify("+ $seconds seconds");
    return $date->format("g\h i\m");// . "h " . $date->format("i") . "m";
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
    if (!$number) return "";
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
    try {
        $header = getHeader("X-Confirm-Dangerous");
        if($header) return true;
    } catch (Exception $e) {
        throw new \Exceptions\HTTP\Confirm($message, $data, $okay, $dangerous);
    }
}

/**
 * 
 * @param string $message - Prompt the client will display with the reauth request
 * @param mixed $resubmit - Data the client must return to complete the reauth request
 * @return true         - This function will only ever return true, it will throw an exception in any failure case
 * @throws Unauthorized - If the user is not logged in
 * @throws Reauthorize  - If the user must reauthorize or fails a password verification
 */
function reauthorize($message = "You must re-authroize your account", $resubmit) {
    // Check if session doesn't exist
    if(!session()) throw new Unauthorized("You must be logged in");
    $reauth_session_name = 'last_reauthorized';
    
    try {
        // Check if the X-Reauthorization header is set
        $reauth = getHeader("X-Reauthorization");
    } catch(Exception $e) {
        $reauth = false;
    }

    if($reauth) {
        $password_plain_text = base64_decode($reauth);
        $session_pword = session('pword');
        if(!password_verify($password_plain_text, $session_pword)) throw new Reauthorize($message, $resubmit);
        $_SESSION[$reauth_session_name] = time();
        return true;
    }
    // Check if the session meets the minimum reauth timeline
    if(!isset($_SESSION[$reauth_session_name]) || time() - $_SESSION[$reauth_session_name] >= app("Auth_reauth_timeout")) {
        throw new Reauthorize($message, $resubmit);
    }

    // If everything checks out, return true;
    return true;
}


function plugin($name) {
    if (isset($GLOBALS['ACTIVE_PLUGINS'][$name])) return $GLOBALS['ACTIVE_PLUGINS'][$name];
    throw new Exception('Plugin is not active!');
}

function get_posts_from_tags(array $tags, string $controller = "Posts"):string {
    $html = "";
    $postController = get_controller($controller, true);
    $posts = $postController->postMan;
    $view = $posts->getTemplate("blurb");
    $result = $posts->getPostsFromTags($tags);

    foreach($result as $post) {
        $html .= view($view, [
            'post' => $post,
            'href' => $postController->path('post',[(string)$post['url_slug']])
        ]);
    }

    return $html;
}

/**
 * 
 * @param iterator $results the results of a Mongo query
 * @param string $schema_name the name of the schema class
 * @return array|null every instance of the mongo query as a Cobalt schema
 */
function results_to_schema($results, string $schema_name) {
    if ($results === null) return null;
    $array  = [];
    // if ($schema_name instanceof \Validation\Normalize === false) throw new Exception("$schema_name is not an instance of \Validation\Normalize");
    foreach ($results as $i => $doc) {
        $array[$i] = new $schema_name($doc);
    }
    return $array;
}

function fetch($url, $method = "GET", $headers = [], $return_headers = false) {
    $client = new \GuzzleHttp\Client();
    $request = $client->request($method, $url, [
        'headers' => $headers
    ]);
    $headers = $request->getHeaders();
    $html = $request->getBody()->getContents();
    if (strpos($headers['Content-Type'][0], 'json')) $html = json_decode($html, true);
    if (!$return_headers) return $html;
    return ['body' => $html, 'headers' => $headers];
}

function post_fetch($url, $data, $headers = [], $return_headers = false) {
    $client = new \GuzzleHttp\Client();
    $request = $client->request('POST', $url, [
        'headers' => $headers,
        'form_params' => $data
    ]);
    $html = $request->getBody()->getContents();
    $headers = $request->getHeaders();
    if (strpos($headers['Content-Type'][0], 'json')) $html = json_decode($html, true);
    if (!$return_headers) return $html;
    return ['body' => $html, 'headers' => $headers];
}

function fetch_and_save($url) {
}


function register_individual_post_routes($collection = __APP_SETTINGS__['Posts']['collection_name'], $schema = "\\Cobalt\\Posts\\PostSchema") {
    $html = "";
    $posts = new PostManager(null, $collection);
    $posts->set_schema($schema);
    $count = $posts->count(['published' => true]);
    foreach($posts->findAllAsSchema(['published' => true], ['limit' => $count + 1]) as $post) {
        $html .= view("sitemap/url.xml", [
            'location' => $post->{'public_link'},
            'lastModified' => $post->publicationDate,
            'priority' => 999,
            // 'additional' => "<changefreq>true</changefreq>"
        ]);
    }
    return $html;
}

/**
 * 
 * @param mixed $remote_url 
 * @param mixed $path 
 * @return bool true on success, false on failure
 */
function fetch_remote_file($remote_url, $path):bool {
    $result = copy($remote_url, $path);
    return $result;
    // return file_put_contents($path, $result);

    // $dir            =   $path;
    // $fileName       =   basename($remote_url);
    // $saveFilePath   =   $dir . $fileName;
    // $ch = curl_init($remote_url);
    // $fp = fopen($path, 'wb');
    // curl_setopt($ch, CURLOPT_FILE, $fp);
    // curl_setopt($ch, CURLOPT_HEADER, 0);
    // $result = curl_exec($ch);
    // curl_close($ch);
    // fclose($fp);
    // return $result;

    // //This is the file where we save the information
    // $fp = fopen($path, 'w+');
    // //Here is the file we are downloading, replace spaces with %20
    // $ch = curl_init(str_replace(" ","%20",$remote_url));
    // // make sure to set timeout to a high enough value
    // // if this is too low the download will be interrupted
    // curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    // // write curl response to file
    // curl_setopt($ch, CURLOPT_FILE, $fp); 
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // // get curl response
    // $result = curl_exec($ch); 
    // curl_close($ch);
    // fclose($fp);

    // return $result;
}

/**
 * This function returns the maximum files size that can be uploaded 
 * in PHP
 * @return int File size in bytes
 **/
function getMaximumFileUploadSize() {
    return min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
}

/**
 * This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
 * 
 * @param string $sSize
 * @return integer The value in bytes
 */
function convertPHPSizeToBytes($sSize) {
    //
    $sSuffix = strtoupper(substr($sSize, -1));
    if (!in_array($sSuffix, array('P', 'T', 'G', 'M', 'K'))) {
        return (int)$sSize;
    }
    $iValue = substr($sSize, 0, -1);
    switch ($sSuffix) {
        case 'P':
            $iValue *= 1024;
            // Fallthrough intended
        case 'T':
            $iValue *= 1024;
            // Fallthrough intended
        case 'G':
            $iValue *= 1024;
            // Fallthrough intended
        case 'M':
            $iValue *= 1024;
            // Fallthrough intended
        case 'K':
            $iValue *= 1024;
            break;
    }
    return (int)$iValue;
}

function async_cobalt_command($command, $context = true, $log = "/dev/null") {
    $shell = __ENV_ROOT__ . "/core.sh";
    if ($context) $shell = __APP_ROOT__ . "/cobalt.sh";
    $pid = shell_exec("nohup nice -n 10 sh $shell $command > $log & printf \"%u\" $!");
    return $pid;
}

function cobalt_command($command, $context = true, $stripControlCharacters = false) {
    $shell = __ENV_ROOT__ . "/core.sh";
    if ($context) $shell = __APP_ROOT__ . "/cobalt.sh";
    if($stripControlCharacters) $shell .= " --plain-output";
    $result = shell_exec("sh $shell $command");
    return $result;
}

function plural($number, string $suffix = "s", string $singular = "") {
    if ($number == 1) return $singular;
    return $suffix;
}


function cookie_consent_check() {
    return isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === "all";
}

function sanitize_path_name($path) {
    return str_replace(["../"], "", $path);
}

function relative_time($time = false, $now = null, $limit = 86400, $format = "M jS g:i A") {
    if($time instanceof UTCDateTime || $time instanceof DriversUTCDateTime) $time = $time->toDateTime();
    if($time instanceof DateTime) $time = $time->getTimestamp();
    if (empty($time) || (!is_string($time) && !is_numeric($time))) $time = time();
    else if (is_string($time)) $time = strtotime($time);

    if(is_null($now)) $now = time();
    $relative = '';

    if ($time === $now) $relative = 'now';
    elseif ($time > $now) $relative = 'in the future';
    else {
        $diff = $now - $time;

        if ($diff >= $limit) $relative = date($format, $time);
        elseif ($diff < 60) {
            $relative = 'less than one minute ago';
        } elseif (($minutes = ceil($diff/60)) < 60) {
            $relative = $minutes.' minute'.(((int)$minutes === 1) ? '' : 's').' ago';
        } else {
            $hours = ceil($diff/3600);
            $relative = 'about '.$hours.' hour'.(((int)$hours === 1) ? '' : 's').' ago';
        }
    }

    return $relative;
}

const FACTOR_MAP = [
    [
        'factor' => 1000,
        'name' => 'thousand',
        'precision' => 1,
        'suffix' => 'k'
    ], [
        'factor' => 1000000,
        'precision' => 1,
        'name' => 'million',
        'suffix' => 'm'
    ], [
        'factor' => 1000000000,
        'precision' => 1,
        'name' => 'billion',
        'suffix' => 'b',
    ], [
        'factor' => 1000000000000,
        'precision' => 1,
        'name' => 'trillion',
        'suffix' => 't',
    ]
];

function pretty_rounding($number, $type = 'suffix', $join = ""):string{
    if($number === 0) return "zero";
    if(is_null($number)) return "zero";
    
    $map = FACTOR_MAP;
    
    if($number < $map[0]['factor']) return $number;

    foreach($map as $data) {
        if($number < $data['factor']) continue;
        if(!key_exists($type, $data)) $type = "suffix";
        $result = round($number / $data['factor'], $data['precision'], PHP_ROUND_HALF_UP) . $join . $data[$type];
    }

    return $result;
}

function pretty_numeral($number):string {
    return pretty_rounding($number, 'name', " ");
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

function obscure_email(string $email, int $threshold = 3, string $character = "•"): string {
    $obscured = "";
    $temp_thresh = $threshold;
    $domain = false;
    for($i = 0; $i <= strlen($email) - 1; $i++) {
        if($email[$i] === "@") {
            $temp_thresh = $threshold;
            $domain = true;
        }
        if($email[$i] === "." && $domain) $temp_thresh = 2;

        if($temp_thresh <= 0) {
            $obscured .= $character;
            continue;
        }

        $obscured .= $email[$i];
        $temp_thresh -= 1;
    }
    return $obscured;
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

function normalize_color($val, $default = null, $normalize = null) {
    if(!$val) $val = "#000000";
    $matches = [];
    $result = preg_match("/^var\((.*)\)$/", $val, $matches);
    if($result) {
        $name = str_replace("--project-","",$matches[1]);
        $val = app("vars-web.$name");
    }

    if (!$val && $default !== null) return $default;
    if (strlen($val) > 8) throw new ValidationIssue("Not a hex color.");
    $pattern = "/^#?[0-9A-Fa-f]{3,6}$/";
    if (!preg_match($pattern, $val)) throw new ValidationIssue("Not a hex color.");
    if($val[0] !== "#" && $normalize) $val = "#$val";
    $length = strlen($val);
    if ($length <= 4) {
        $one = 1;
        $two = 2;
        $three = 3;
        if($val[0] !== "#") {
            $one = 0;
            $two = 1;
            $three = 2;
        }
        $val = "#$val[$one]$val[$one]$val[$two]$val[$two]$val[$three]$val[$three]";
    }
    return preg_replace("/#{2,}/","#",strtoupper($val));
}


/**
 * Clamps a value between the $min and $max value;
 * @param int|float $int 
 * @param int|float $min 
 * @param int|float $max 
 * @return int|float 
 */
function clamp(int|float $current, int|float $min, int|float $max):int|float {
    return max($min, min($max, $current));
}

function country2flag(?string $countryCode, ?string $countryName = null): string {
    if(!$countryCode) return "";
    $unicode = (string) preg_replace_callback(
        '/./',
        static fn (array $letter) => mb_chr(ord($letter[0]) % 32 + 0x1F1E5),
        $countryCode
    );
    return "<span title='$countryName' draggable='false'>" . $unicode . "</span>";
}

function getHeader($header, $headerList = null, $latest = true, $exception = true) {
    if($headerList === null) $headerList = getallheaders();
    $toMatch = strtolower($header);
    $headers = [];
    foreach($headerList as $key => $value){
        $headers[strtolower($key)] = $value;
    }
    $match = null;
    if(key_exists($toMatch, $headers)) $match = $headers[$toMatch];

    if(gettype($match) === "array" && $latest) return $match[count($match) - 1];
    if($match) return $match;
    if($exception) throw new NoValue("The specified header was not found among the request headers");
    return null;
}

function syntax_highlighter($code, $filename = "", $language = "json", $line_numbers = true, $action_panel = false) {
    if(gettype($code) !== "string") $code = json_encode($code, JSON_PRETTY_PRINT);
    $mutant = "<pre data-file='$filename' data-lang='$language'>$code</pre>";
    $highlighter = new Highlighter($mutant, 'railscasts');
    $highlighter->setShowLineNumbers($line_numbers);
    $highlighter->setShowActionPanel($action_panel);
    return $highlighter->parse();
}

function createJWT(array $header, array $payload, $secret) {
    // Create token header as a JSON string
    $header = json_encode(array_merge([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ],$header));

    // Create token payload as a JSON string
    $payload = json_encode($payload);

    // Encode Header to Base64Url String
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Encode Payload to Base64Url String
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

    // Encode Signature to Base64Url String
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    return $jwt;
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

/**
 * Special Query Keywords
 *   @form - References the form-request element that submitted the request (if any)
 *   @cookie - [set, remove] Updates a cookie value through an async reques
 *   sessionStorage - [set, remove] Stores or removes sessionStorage data
 *   localStorage - [set, remove] Stores or removes localStorage data
 * 
 * Implemented Update Parameters
 *   value - <mixed> Modify the value property of the target
 *   dispatchEvent - [event => [detail => [...]]] Dispatches an event on the target
 *   innerHTML - <string> Updates the innerHTML of the element
 *   outerHTML - <string> Updates the outerHTML of the element
 *   invalid - [true|false] Sets the `invalid` property
 *   delete - [true] Deletes the target element
 *   remove - [true] Removes the target element | <string> Queries the target for the given string and removes matching element(s)
 *   message - <string> will provide a message to the end user. It will look like a ValidationIssue
 *   src - <string> update the src attribute for an img tag
 *   attribute - <string> update arbitrary attribute
 *   attributes - <array> update a list of attributes
 *   style - <array> update a list of styles
 *   clear - [true] (@form only) - Clears the form that initiated this request
 * 
 * @param string $query 
 * @param array $value 
 * @return void 
 */
function update(string $query, array $value) {
    global $context_processor;
    if($context_processor instanceof ApiHandler === false) return;
    $context_processor->update_instructions[] = ['target' => $query, ...$value];
}

/**
 * Redirect will set a response header of either "X-Location" if "X-Request-Source"
 * is among request headers or "Location" if it's not.
 * @param string $path - The path to redirect to
 * @return void 
 */
function redirect(string $path) {
    $headers = apache_request_headers();
    // If the request was sent via AsyncFetch, return `X-Location` header
    if(key_exists("X-Request-Source",$headers)) {
        header("X-Redirect: $path");
        return;
    }
    // Otherwise, return `Location` header
    header("Location: $path");
}

/**
 * Sets a response header using the `redirect` function and then exits.
 * @param string $path 
 * @return never 
 */
function redirect_and_exit(string $path): never {
    redirect($path);
    exit;
}

/**
 * Supply a custom content group name and this function will return a hyperlink
 * for authorized user accounts where they can edit content.
 * 
 * Use this to manually place an edit link for groups of like content.
 * @param string $group 
 * @return string 
 * @throws Exception 
 */
function edit_link($group) {
    try {
        if(!has_permission("Customizations_modify", null, null, false)) return "";
    } catch (\Exceptions\HTTP\Unauthorized $e) {
        return "";
    }
    return "<a class='custom-element-edit-link' href='/admin/customizations/".urlencode($group)."'><i name='pencil'></i></a>";
}

/**
 * Will return the $_FILES superglobal to a more sane format:
 * [
 *    [0] => Array
 *        (
 *             [input_name] => 'example',
 *             [name]       => 'example.jpg',
 *             [type]       => 'image/jpeg',
 *             [tmp_name]   => 'tmp/php8830t4',
 *             [error]      => 0,
 *             [size]       => 21509
 *        )
 * ]
 * @return array 
 */
function normalize_file_array() {
    $fileUploadArray = $_FILES;
    $resultingDataStructure = [];
    foreach ($fileUploadArray as $input => $infoArr) {
        $filesByInput = [];
        $nextIndex = count($filesByInput);
        foreach ($infoArr as $key => $valueArr) {
            if (is_array($valueArr)) { // file input "multiple"
                foreach($valueArr as $i=>$value) {
                    $filesByInput[$i][$key] = $value;
                }
                
            }
            else { // -> string, normal file input
                $filesByInput[] = array_merge($infoArr, ['input_name' => $input]);
                break;
            }
        }
        $filesByInput[$nextIndex]['input_name'] = $input;
        $resultingDataStructure = array_merge($resultingDataStructure,$filesByInput);
    }
    $filteredFileArray = [];
    foreach($resultingDataStructure as $file) { // let's filter empty & errors
        if (!$file['error']) $filteredFileArray[] = $file;
    }
    return $filteredFileArray;
}


/**
 * Given this structure:
 * [
 *    "key" => [
 *       "value" => [
 *           "nested" => true
 *       ],
 *       "other" => false
 *    ],
 *    ...
 * ]
 * 
 * This function will return:
 * [
 *    "key.value.nested" => true,
 *    "key.other" => false,
 *    ...
 * ]
 * @param mixed $array 
 * @param string $toplevel 
 * @return void 
 */
// function flatten_array_to_js_notation($array, $toplevel = null) {
//     $flattened = [];
//     // if($toplevel) $toplevel = "$toplevel.";
//     foreach($array as $key => $val) {
//         $mutant = [];
//         if(is_object($val) && $val instanceof jsonSerializable) {
//             $val = $val->__jsonSerialize();
//         }
//         if(is_array($val)) {
//             $val = flatten_array_to_js_notation($array, $key);
//             continue;
//         }
//         // $newkey = $toplevel.$key;
//         $flattened[$newkey] = 
//     }
// }

function convertFractionToChar($string) {
    return str_replace(" ", "", str_replace(
        ["1/4",   "1/2",   "3/4",   "1/7",    "1/9",    "1/10",   "1/3",    "2/3",    "1/5",    "2/5",    "3/5",    "4/5",    "1/6",    "5/6",    "1/8",    "3/8",    "5/8",    "7/8"],
        ["&#188;","&#189;","&#190;","&#8528;","&#8529;","&#8530;","&#8531;","&#8532;","&#8533;","&#8534;","&#8535;","&#8536;","&#8537;","&#8538;","&#8539;","&#8540;","&#8541;","&#8542;"],
        $string
    ));
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