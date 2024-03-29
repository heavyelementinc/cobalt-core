<?php
// Let's make sure we're running a suppoted version of PHP (since we use the 
// not-insane [] array syntax, the spread "..." syntax, and match expressions)

if (!version_compare(PHP_VERSION, "8.1", ">=")) die("You must be running PHP version 8.1.0 or greater (".PHP_VERSION.")");

/* Cobalt Version Number */
define("__COBALT_VERSION", "2.0");

/* A list of modules we don't allow along with anonymous funcitons which may
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

if($match) die("The following PHP modules are incompatible with Cobalt Engine but they're enabled on your system:<br>$blacklist");

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

if($missing !== "") die("Your environment is misconfigured! Please install the following required packages.<br>$missing");

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

if($missing !== "") die("Your runtime is missing the following required functions!<br>$missing");

// if(!in_array(get_current_user(), ["www-data", "apache"])) die ("You must be running Cobalt as the web server user.");

// if(app("always_add_missing_trailing_slash")) {
//     $path_info = pathinfo($_SERVER['REQUEST_URI']);
//     if($path_info['filename']) {
//         exit;
//     }
// }
