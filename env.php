<?php

/**
 * env.php - The Cobalt Environment Bootstrapper
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * Defines Cobalt's constants as well as loads the settings file for the current
 * project (internally referred to as an APP). The cobalt-core directory can
 * serve many apps at once but will only ever execute a single app while
 * fulfilling a request.
 * 
 * These files will not do *anything* unless invoked from within the context of
 * an APP. Please create a new app using the CLI and configure your webserver to
 * point to the app's /public directory.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 - Heavy Element, Inc.
 */

// Let's make sure we're running a suppoted version of PHP (since we use 
// the not-insane array syntax and the spread "..." syntax)
if (!version_compare(PHP_VERSION, "7.4", ">=")) die("You must be running PHP version 7.4 or greater");

/* Cobalt Version Number */
define("__COBALT_VERSION", "0.1");

/* ENV_ROOT defines the root of the core files (the dir this file resides in) */
define("__ENV_ROOT__", __DIR__);

// Establish our app root
$app_root = "";
// Go up one directory so we're not in the public space
if (!empty($_SERVER['DOCUMENT_ROOT'])) $app_root = $_SERVER['DOCUMENT_ROOT'] . "/../";
// Rely on the Cobalt CLI to mandate the path to our app
else if (key_exists("cli_app_root", $GLOBALS)) $app_root = $GLOBALS['cli_app_root'];
else die("Cannot establish absolute path to app root"); // Die.

define("__APP_ROOT__", realpath($app_root));
define("__PLG_ROOT__", __APP_ROOT__ . "/plugins");

// Define a few values that we will use to handle writing output during an exception
$allowed_to_exit_on_exception = true;
$write_to_buffer_handled = false;

// Let's import our exceptions and our helper functions:
require_once __DIR__ . "/globals/global_exceptions.php";
require_once __DIR__ . "/globals/global_functions.php";
// Import Composer's autoload
$composer = __DIR__ . "/vendor/autoload.php";
if (!file_exists($composer)) die("Dependencies have not been installed. Run `composer install` in the cobalt-core directory as your webserver user");
require_once $composer;

// And then define our own autoload function (specified in global_functions.php)
spl_autoload_register("cobalt_autoload", true);

/** @global TIME_TO_UPDATE determines if we need to rebuild our cached assets */
$GLOBALS['time_to_update'] = false;
// $update_list = [
//     __APP_ROOT__ . "/private/config/settings.json",
//     __APP_ROOT__ . "/private/config/settings.jsonc",
//     __ENV_ROOT__ . "/config/settings.json",
// ];

// foreach($update_list as $file) {
//     if(file_exists($file) && filemtime($file)) {
//         $GLOBALS['time_to_update'] = true;
//         break;
//     }
// }

try {
    // Load our ACTIVE plugins.
    require_once __ENV_ROOT__ . "/globals/plugins.php";
} catch (Exception $e) {
    die($e->getMessage());
}

// Instantiate our settings (`true` for loading settings from cache)
try {
    $application = new SettingsManager(true);
    /** @global $app How we set up and process our settings */
    $app = $application;
} catch (Exception $e) {
    die($e->getMessage());
}

/** @global __APP_SETTINGS__ The __APP_SETTINGS__ constant is an array of app 
 *                           settings 
 * */
define("__APP_SETTINGS__", $application->get_settings());

$depends = __APP_SETTINGS__['cobalt_version'] ?? __COBALT_VERSION;
if (!version_compare($depends, __COBALT_VERSION, ">=")) die("This app depends on version $depends of Cobalt Engine. Please upgrade.");

/** If we're NOT in a CLI environment, we should import the context processor */
if (!defined("__CLI_ROOT__")) require_once __ENV_ROOT__ . "/globals/context.php";
