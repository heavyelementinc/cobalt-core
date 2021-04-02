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
$GLOBALS['allowed_to_exit_on_exception'] = true;
$GLOBALS['write_to_buffer_handled'] = false;

// Let's import our exceptions and our helper functions:
require_once __DIR__ . "/globals/global_exceptions.php";
require_once __DIR__ . "/globals/global_functions.php";
// Import Composer's autoload
require_once __DIR__ . "/vendor/autoload.php";
// And then define our spl_autoload method
spl_autoload_register("cobalt_autoload",true);

// Instantiate our settings (true for loading settings from cache)
$application = new SettingsManager(true);
$GLOBALS['app'] = $application;
define("__APP_SETTINGS__",$application->get_settings());

/** Now we need to determine which routing tables we need to load */
$GLOBALS['route_context'] = Routes\Route::get_router_context($_SERVER['REQUEST_URI']);

$GLOBALS['auth'] = new Auth\Authentication(); // Get our user
/** Let's set our context_processor to web since we want that to be default */
$processor = "Web\WebHandler";

/** TODO: Finish the initial setup process!!!!! */
// if(file_exists(__APP_ROOT__ . '/private/config/setup')){
    /** Handle normal execution */
    if($GLOBALS['route_context'] !== "web") $processor = app("api_routes")[$GLOBALS['route_context']]['processor'];
    
    $GLOBALS['context_processor'] = new $processor();
    // From here, the router should take care of everything.
    $GLOBALS['router'] = new Routes\Router($GLOBALS['route_context']);
    $GLOBALS['router']->get_routes();

// } else {
//     /** Handle setup if we need to */
//     $GLOBALS['route_context'] = "web";
//     $processor = "Web\WebHandler";
//     require __ENV_ROOT__ . "/globals/init/setup.php";
//     $GLOBALS['router'] = new Routes\Router($GLOBALS['route_context']);
//     \Routes\Route::get("/", "Setup@init");
//     \Routes\Route::post("/complete", "Setup@complete");
// }

if(method_exists($GLOBALS['context_processor'],'post_router_init')) $GLOBALS['context_processor']->post_router_init();
$GLOBALS['router']->discover_route();
$GLOBALS['router_result'] = $GLOBALS['router']->execute_route();
if(method_exists($GLOBALS['context_processor'],'post_router_execute')) $GLOBALS['context_processor']->post_router_execute();

// $err_handler = set_exception_handler("exception_handler"); // At some point we should figure out handling erros other than our HTTP exceptions
// throw new Exception("Major exception"); // Testing exception

