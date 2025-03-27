<?php
// Let's make sure we're running a suppoted version of PHP (since we use the 
// not-insane [] array syntax, the spread "..." syntax, and match expressions)

if (!version_compare(PHP_VERSION, "8.1", ">=")) kill("You must be running PHP version 8.1.0 or greater (".PHP_VERSION.")");

/* Cobalt Version Number */
define("__COBALT_VERSION", "2.1.64");

/* A list of modules we don't allow along with anonymous functions which may
   configure it to work correctly.
*/
$module_blacklist = [
    'uopz' => function () {
        if(function_exists("uopz_allow_exit")) {
            uopz_allow_exit(true);
            return true;
        }
        return false;
    }
];

$match = "";

foreach($module_blacklist as $blacklist => $function) {
    if(extension_loaded($blacklist) && !$function()) $match .= " $blacklist<br>";
}

if($match) kill("The following PHP modules are incompatible with Cobalt Engine but they're enabled on your system:<br>$blacklist");

// The following are PHP dependencies
$dependencies = [
    "dom",
    "mongodb",
    "libxml",
    // "mcrypt", // Retired mcrypt dependency
    // "protobuf", // Retired protobuf dependency
    "yaml",
    "standard",
    "date",
    "pcre",
    "json",
    "exif",
    "gd",
    "fileinfo",
    "filter",
    "SPL",
    "ctype",
    "readline",
    "apcu",
    "mbstring",
    "session",
    "hash",
    "imap",
    "intl",
    "openssl",
    "tokenizer",
    "zlib",
    "gmp",
    "bcmath",
    "igbinary",
    "curl",
    // "ERROR FOR TESTING PURPOSES"
];

$missing = "";

// Let's ensure that we have all the required dependencies.
foreach($dependencies as $dependency) {
    if(!extension_loaded($dependency)) $missing .= " $dependency<br>";
}

if($missing !== "") kill("Your environment is misconfigured! Please install the following required packages.<br>$missing");

$required_functions = [
    'imagecreatefromjpeg',
    'imagejpeg',
    'imagecreatefrompng',
    'imagepng',
    'imagecreatefromgif',
    'imagegif',
    'imagecreatefromwebp',
    'imagewebp',
    'imagecreatefromavif',
    'imageavif',

    "exif_imagetype",
    "exif_read_data",
    "imageflip",
    "imagerotate",
    "imagesx",
    "imagesy",
    "imagecreatetruecolor",
    "imagecolortransparent",
    "imagecolorallocate",
    "imagealphablending",
    "imagesavealpha",
    "imagecopyresampled",
    
    "get_current_user",
    // 'apache_request_headers',
    // 'ERROR FOR TESTING PURPOSES'
];

$missing = "";

foreach($required_functions as $funct) {
    if(!function_exists($funct)) $missing .= " $funct<br>";
}

if($missing !== "") kill("Your runtime is missing the following required functions!<br>$missing");

// if(!in_array(get_current_user(), ["www-data", "apache"])) die ("You must be running Cobalt as the web server user.");

// if(app("always_add_missing_trailing_slash")) {
//     $path_info = pathinfo($_SERVER['REQUEST_URI']);
//     if($path_info['filename']) {
//         exit;
//     }
// }

const __ENV_COMPOSER__ = __ENV_ROOT__ . "/composer.json";
const __APP_COMPOSER__ = __APP_ROOT__ . "/composer.json";

// Let's make sure that if our app has a `composer.json` file, then it at a bare
// minimum requires the same dependencies as Cobalt Engine. (This should prevent
// the weird errors where packages installed in `core` cannot be loaded)
if(file_exists(__APP_COMPOSER__)) {
    $composer = __APP_ROOT__ . "/vendor/autoload.php";
    $__dependency_dir = "app root";
    $env_comp = json_decode(file_get_contents(__ENV_COMPOSER__), true);
    $app_comp = json_decode(file_get_contents(__APP_COMPOSER__), true);
    $intersection = array_intersect_key($env_comp['require'] ?? [], $app_comp['require'] ?? []);
    if($intersection !== $env_comp['require']) {
        kill("Your app configuration specifies a <code>composer.json</code> file but it is missing at least one Cobalt Engine dependency!", INTERNAL_SERVER_ERROR);
    }
    unset($env_comp, $app_comp, $intersection);
} else {
    $composer = __ENV_ROOT__ . "/vendor/autoload.php";
    $__dependency_dir = "cobalt-core";
}