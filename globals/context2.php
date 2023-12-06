<?php

/**
 * context.php - The Cobalt Context Bootstrapper
 * 
 * Copyright 2022 - Heavy Element, Inc
 * 
 * This file handles configuring the router context, establishing authentication 
 * parameters, and executing the context instructions using the context processor
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 */

use Exceptions\HTTP\HTTPException;

ob_start();

/** We need to determine which routing tables we need to load 
 * @global $route_context Stores the value of the route context
 */
$route_context = Routes\Route::get_router_context($_SERVER['REQUEST_URI']);

try {
    /** @global $auth Access the Authentication class */
    $auth = new Auth\Authentication();
} catch (Exception $e) {
    die($e->getMessage());
}

// Let's set our processor to 'Web\WebHandler' since we want that to be default
$kernel_name = "\Cobalt\Kernel\Web";
$permission_needed = false;
/** Check if we're actually in a web context and, if not, get the name of the
 * appropriate context processor. */
if ($route_context !== "web") {
    $kernel_name = app("context_prefixes")[$route_context]['processor'];
    $permission_needed = app("context_prefixes")[$route_context]['permission'] ?? false;
}

try {
    /** 
     * 
     * @todo Handle new project initialization here 
     * 
     * */

    // Let's get find our current route and then execute
    $router = new Routes\Router($route_context);
    $router->init_route_table();
    $router->get_routes();
    $current_route_meta = $router->discover_route();

    // Invoke our context kernel.
    $kernel = new $kernel_name();

    if (!is_a($kernel, "Cobalt\Kernel\Request")) {
        if (app("debug_exceptions_publicly")) die("Context processor must be an instance of Cobalt\Kernel\Request");
        else die("Error");
    }

    // Now we're ready to execute our route
    $exec_result = $router->execute_route();


    $kernel->__initialize(app("context_prefixes")[$route_context]);
    $kernel->__route_data($current_route_meta);
    
    $context_result = $kernel->__prepare_output();

} catch(HTTPException $e) {
    ob_clean();
    $context_result = $kernel->__exception_handler($e);
} catch(Exception $e) {
    ob_clean();
    $context_result = $kernel->__exception_handler($e);
} catch(Error $e) {
    ob_clean();
    $context_result = $kernel->__exception_handler($e);
}


if($context_result !== null) {
    echo $context_result;
    ob_flush();
    exit;
} else {
    // die("No content in buffer");
}