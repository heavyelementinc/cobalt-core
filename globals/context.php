<?php
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

