<?php
/**
 * context.php - The Cobalt Context Bootstrapper
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * This file handles configuring the router context, establishing authentication 
 * parameters, and executing the context instructions using the context processor
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 */


/** We need to determine which routing tables we need to load 
 * @global $route_context Stores the value of the route context
*/
$route_context = Routes\Route::get_router_context($_SERVER['REQUEST_URI']);

/** @global $auth Access the Authentication class */
$auth = new Auth\Authentication();

// Let's set our processor to 'Web\WebHandler' since we want that to be default
$processor = "Web\WebHandler";

/** Check if we're actually in a web context and, if not, get the name of the
 * appropriate context processor. */
if($route_context !== "web") $processor = app("api_routes")[$route_context]['processor'];

// Invoke our context processor.
$context_processor = new $processor();

// The router takes care of much of the rest of this process.
$router = new Routes\Router($route_context);

// We load our routing tables for the current context
$router->get_routes();

/** Here we check if any "router stage" methods exist in our context processor
 * and we execute them if they do, then we move on to the next router stage. */
if(method_exists($context_processor,'post_router_init')) $context_processor->post_router_init();

$router->discover_route();
if(method_exists($context_processor,'post_router_discovery')) $context_processor->post_router_discovery();

$router_result = $router->execute_route();
if(method_exists($context_processor,'post_router_execute')) $context_processor->post_router_execute();




/** @todo Finish the initial setup process!!!!! */

// if(file_exists(__APP_ROOT__ . '/private/config/setup')){
// } else {
//     /** Handle setup if we need to */
//     $route_context = "web";
//     $processor = "Web\WebHandler";
//     require __ENV_ROOT__ . "/globals/init/setup.php";
//     $router = new Routes\Router($route_context);
//     \Routes\Route::get("/", "Setup@init");
//     \Routes\Route::post("/complete", "Setup@complete");
// }


// At some point we should figure out handling erros other than our HTTP exceptions
// $err_handler = set_exception_handler("exception_handler");
// throw new Exception("Major exception"); // Testing exception