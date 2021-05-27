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

ob_start();

/** We need to determine which routing tables we need to load 
 * @global $route_context Stores the value of the route context
 */
$route_context = Routes\Route::get_router_context($_SERVER['REQUEST_URI']);


/** @global $auth Access the Authentication class */
$auth = new Auth\Authentication();

// Let's set our processor to 'Web\WebHandler' since we want that to be default
$processor = "Handlers\WebHandler";
$permission_needed = false;
/** Check if we're actually in a web context and, if not, get the name of the
 * appropriate context processor. */
if ($route_context !== "web") {
    $processor = app("context_prefixes")[$route_context]['processor'];
    $permission_needed = app("context_prefixes")[$route_context]['permission'] ?? false;
}

// Invoke our context processor.
$context_processor = new $processor();

if (!is_a($context_processor, "Handlers\RequestHandler")) {
    if (app("debug")) die("Context processor must be an instance of Handlers\RequestHandler");
    else die("Error");
}

// We use _stage_bootstrap as a means of keeping track of where we are in the
// bootstrapping process. This gives us insight we can later use to handle any
// errors which might arise before we're ready to present errors to the client.
$context_processor->_stage_bootstrap = [
    '_stage_init'    => false,  '_stage_route_discovered' => false,
    '_stage_execute' => false,  '_stage_output'           => false,
];
try {
    // Check if we need to initialize Cobalt and start initialization if needed.
    // When we init, we change the route_context to "init" so as to ignore all
    // other web routes.
    $init_file = __APP_ROOT__ . "/ignored/init.json";

    // Check the settings to see if user accounts are enabled, and then check if we
    // have set the current file.
    if ($route_context === "web" && app("Auth_user_accounts_enabled") && !file_exists("$init_file.set")) {
        require_once __ENV_ROOT__ . "/globals/init.php";
    }

    // The router takes care of much of the rest of this process.
    $router = new Routes\Router($route_context);

    // Create the routing table for the current context so that the Cobalt init
    // script has something to bind its routes to.
    $router->init_route_table();

    // We load our routing tables for the current context
    $router->get_routes();

    $context_processor->_stage_init(app("context_prefixes")[$route_context]);
    $context_processor->_stage_bootstrap['_stage_init'] = true;

    /** @global string PATH contains either an empty string the URI ends in '/'
     * or "../" if the URI ends without '/' also available in rendering engine 
     * as {{PATH}} */
    $GLOBALS['PATH'] = "";
    /** @global array $current_route_meta contains the discovered route's metadata */
    $current_route_meta = $router->discover_route();

    $context_processor->_stage_route_discovered(...$current_route_meta);
    $context_processor->_stage_bootstrap['_stage_route_discovered'] = true;

    $router_result = $router->execute_route();
    $context_processor->_stage_execute($router_result);
    $context_processor->_stage_bootstrap['_stage_execute'] = true;

    $context_processor->_stage_output();
    $context_processor->_stage_bootstrap['_stage_output'] = true;
    ob_flush(); // Write the output buffer to the client
} catch (Exceptions\HTTP\HTTPException $e) {
    ob_clean(); // Clear the output buffer
    $context_processor->_public_exception_handler($e);
    exit;
} catch (Exception $e) {
    header("HTTP/1.0 500 Unknown Error");
    if (app("debug")) die($e->getMessage());
    else die("An unknown error has occurred.");
    exit;
}
