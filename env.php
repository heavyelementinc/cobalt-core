<?php
$GLOBALS['BENCHMARK_RESULTS']['env_invoke'] = ['start' => microtime(true) * 1000];

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

require_once __DIR__ . "/globals/logs.php";
// Let's make sure our environment is configured properly.
require_once __DIR__ . "/globals/env_probe.php";

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

// Let's ensure that the ignored config directory exists
$ignored_config_dir = __APP_ROOT__ . "/ignored/config/";
if (!file_exists($ignored_config_dir)) mkdir($ignored_config_dir, 0777, true);

// Define a few values that we will use to handle writing output during an exception
$allowed_to_exit_on_exception = true;
$write_to_buffer_handled = false;

require_once __DIR__ . "/globals/global_declarations.php";
require_once __DIR__ . "/globals/bootstrap.php";
// Let's import our exceptions and our helper functions:
require_once __DIR__ . "/globals/global_exceptions.php";
require_once __DIR__ . "/globals/global_functions.php";
require_once __DIR__ . "/globals/global_csrf.php";

$app_env = __APP_ROOT__ . "/app_env.php";
if(file_exists($app_env)) require_once $app_env;

// Import Composer's autoload
$composer = __DIR__ . "/vendor/autoload.php";
if (!file_exists($composer)) die("Dependencies have not been installed. Run `composer install` in the cobalt-core directory as your webserver user");
require_once $composer;

// And then define our own autoload function (specified in global_functions.php)
spl_autoload_register("cobalt_autoload", true);

try {
    // Load our ACTIVE plugins.
    require_once __ENV_ROOT__ . "/globals/plugins.php";
} catch (Exception $e) {
    die($e->getMessage());
}

try {
    $application = new \Cobalt\Settings\Settings();
    /** @global $app How we set up and process our settings */
    $app = $application;
} catch (Exception $e) {
    die($e->getMessage());
} catch (Error $e) {
    die($e->getMessage());
}

// Let's find our trusted cobalt domain
$_SERVER['COBALT_TRUSTED_HOST'] = null;
if(in_array($_SERVER['HTTP_HOST'], $app->__settings->API_CORS_allowed_origins->getArrayCopy())) {
    $_SERVER['COBALT_TRUSTED_HOST'] = $_SERVER['HTTP_HOST'];
    $app->__settings->trusted_host = $_SERVER['COBALT_TRUSTED_HOST'];
}
/** @global __APP_SETTINGS__ The __APP_SETTINGS__ constant is an array of app 
 *                           settings 
 * */
define("__APP_SETTINGS__", $application->get_settings());


session_name("COBALTID");
$cobalt_session_started = session_start([
    'cookie_lifetime' => app('Auth_session_days_until_expiration') * 24 * 60 * 60,
    // 'cookie_httponly' => !__APP_SETTINGS__['require_https_login_and_cookie'],
    // 'cookie_secure' => !__APP_SETTINGS__['require_https_login_and_cookie']
]);

if(!key_exists("cli_app_root", $GLOBALS) && $cobalt_session_started === false && app('Auth_logins_enabled')) die("Something went wrong creating a session. Do you have cookies disabled? They're required for this app.");


$depends = __APP_SETTINGS__['cobalt_version'] ?? __COBALT_VERSION;
if (!version_compare($depends, __COBALT_VERSION, ">=")) die("This app depends on version $depends of Cobalt Engine. Please upgrade.");

/** If we're NOT in a CLI environment, we should import the context processor */
if (!defined("__CLI_ROOT__")) require_once __ENV_ROOT__ . "/globals/context.php";
