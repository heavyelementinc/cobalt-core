<?php
// if(empty($_SERVER['HTTPS'])) die("Connection not secure.");
/**
 * Define our environment variables so we have absolute knowledge of where we are
 * in the filesystem. We rely on our webserver to tell us the root project.
 */
define("__ENV_ROOT__", __DIR__);

/** Establish our app root */
$app_root = "";
if(isset($_SERVER['DOCUMENT_ROOT'])) $app_root = $_SERVER['DOCUMENT_ROOT'] . "/../"; // Go up one directory so we're not in the public space
else if(isset($GLOBALS['cli_app_root'])) $app_root = $GLOBALS['cli_app_root']; // Rely on the Cobal CLI to mandate the path to our app
else die("Cannot establish absolute path to app root"); // Die.

define("__APP_ROOT__", realpath($app_root));

// Define a few values that we will use to handle writing output during an exception
$allowed_to_exit_on_exception = true;
$write_to_buffer_handled = false;

// Let's import our exceptions and our helper functions:
require_once __DIR__ . "/globals/global_exceptions.php";
require_once __DIR__ . "/globals/global_functions.php";
// Import Composer's autoload
require_once __DIR__ . "/vendor/autoload.php";
// And then define our spl_autoload method
spl_autoload_register("cobalt_autoload",true);

// Instantiate our settings (true for loading settings from cache)
try{
    $application = new SettingsManager(false);
} catch (Exception $e){
    die($e->getMessage());
}
/** @global $app How we set up and process our settings */
$app = $application;

/** @global __APP_SETTINGS__ The __APP_SETTINGS__ constant is an array of app 
 *                           settings 
 * */
define("__APP_SETTINGS__",$application->get_settings());

/** If we're NOT in a CLI environment, we should import the context processor */
if(!defined("__CLI_ROOT__")) require_once __ENV_ROOT__ . "/globals/context.php";