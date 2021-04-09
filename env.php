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

/** Now we need to determine which routing tables we need to load 
 * @global $route_context Stores the value of the route context
*/
$route_context = Routes\Route::get_router_context($_SERVER['REQUEST_URI']);

/** @global $auth Access the Authentication class */
$auth = new Auth\Authentication();

/** Let's set our context_processor to web since we want that to be default */
$processor = "Web\WebHandler";

/** TODO: Finish the initial setup process!!!!! */
// if(file_exists(__APP_ROOT__ . '/private/config/setup')){
    /** Handle normal execution */
    if($route_context !== "web") $processor = app("api_routes")[$route_context]['processor'];
    
    $context_processor = new $processor();
    // From here, the router should take care of everything.
    $router = new Routes\Router($route_context);
    $router->get_routes();

// } else {
//     /** Handle setup if we need to */
//     $route_context = "web";
//     $processor = "Web\WebHandler";
//     require __ENV_ROOT__ . "/globals/init/setup.php";
//     $router = new Routes\Router($route_context);
//     \Routes\Route::get("/", "Setup@init");
//     \Routes\Route::post("/complete", "Setup@complete");
// }

if(method_exists($context_processor,'post_router_init')) $context_processor->post_router_init();
$router->discover_route();
if(method_exists($context_processor,'post_router_discovery')) $context_processor->post_router_discovery();
$router_result = $router->execute_route();
if(method_exists($context_processor,'post_router_execute')) $context_processor->post_router_execute();

// $err_handler = set_exception_handler("exception_handler"); // At some point we should figure out handling erros other than our HTTP exceptions
// throw new Exception("Major exception"); // Testing exception

