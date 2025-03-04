<?php

use Auth\UserPersistance;
use MongoDB\BSON\ObjectId;

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
    global $session;
    if(is_cli()) {
        $session = new UserPersistance([
            '_id' => new ObjectId(),
            'fname' => 'Cobalt',
            'lname' => 'Engine',
            'uname' => '__cobalt_engine_cli',
            'email' => 'dummy@heavyelement.com',
            // 'flags' => UserPersistance::STATE_USER_VERIFIED,
            'groups' => ['root'],
            'permissions' => [],
            'is_root' => true,
        ]);
    }
    if (!isset($session)) return null;
    if ($info === null) return $session ?? null;
    if (key_exists($info, $session->__dataset ?? [])) return $session->{$info}?->getValue() ?? null;
    return lookup_js_notation($info, $session, true);
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
 * Check if the current user has permission.
 *
 * @throws \Exceptions\HTTP\Unauthorized if not logged in
 * @throws Exception if the permission specified does not exist
 * @param  string $perm_name the name of the permission to check for
 * @param  string|array $group the group name or list of group names. 
 *                      Can be null.
 * @return bool true if the user has permission, false otherwise
 */
function has_permission($perm_name, $group = null, ?UserPersistance $user = null, $throw_no_session = true):bool {
    if(is_cli()) return true;
    return $GLOBALS['auth']->has_permission($perm_name, $group, $user, $throw_no_session);
}

/**
 * Checks if the current user has root permission
 * @return bool
 */
function is_root():bool {
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

function obfuscate_path_name(string $path_name):string {
    return str_replace([__ENV_ROOT__, __APP_ROOT__], ['__ENV_ROOT__', '__APP_ROOT__'], $path_name);
}