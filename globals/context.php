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
benchmark_start("router_setup");
ob_start();

/** We need to determine which routing tables we need to load 
 * @global $route_context Stores the value of the route context
 */
$route_context = Routes\Route::get_router_context($_SERVER['REQUEST_URI']);
if(getenv('HTTP2')) require_once __ENV_ROOT__ . "/globals/http2.php";
try {
    /** @global $auth Access the Authentication class */
    $auth = new Auth\Authentication();
} catch (Exception $e) {
    kill($e->getMessage());
}
$WEB_PROCESSOR_VARS['custom'] = new Cobalt\Customization\CustomizationManager();

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
/**
 * @global \Handlers\RequestHandler
 */
$context_processor = new $processor();

if (!is_a($context_processor, "Handlers\RequestHandler")) {
    if (app("debug_exceptions_publicly")) kill("Context processor must be an instance of Handlers\RequestHandler");
    else kill("Error");
}

define("__APP_CONTEXT__", __APP_ROOT__ . "/app_context.php");
if(file_exists(__APP_CONTEXT__)) require_once __APP_CONTEXT__;

// We use _stage_bootstrap as a means of keeping track of where we are in the
// bootstrapping process. This gives us insight we can later use to handle any
// errors which might arise before we're ready to present errors to the client.
$context_processor->_stage_bootstrap = [
    '_stage_init'    => false,  '_stage_route_discovered' => false,
    '_stage_execute' => false,  '_stage_output'           => false,
];
$context_result = null;
try {
    // Check if we need to initialize Cobalt and start initialization if needed.
    // When we init, we change the route_context to "init" so as to ignore all
    // other web routes.
    $init_file = __APP_ROOT__ . "/ignored/init";

    // Check the settings to see if user accounts are enabled, and then check if we
    // have set the current file.
    if ($route_context === "web" && app("Auth_user_accounts_enabled") && !file_exists($init_file)) {
        require_once __ENV_ROOT__ . "/globals/init.php";
    }


    // The router takes care of much of the rest of this process.
    $ROUTER = new Routes\Router($route_context);

    // Create the routing table for the current context so that the Cobalt init
    // script has something to bind its routes to.
    $ROUTER->init_route_table();

    // We load our routing tables for the current context
    $ROUTER->get_routes();

    $context_processor->_stage_init(app("context_prefixes")[$route_context]);
    $context_processor->_stage_bootstrap['_stage_init'] = true;

    /** @global string PATH contains either an empty string the URI ends in '/'
     * or "../" if the URI ends without '/' also available in rendering engine 
     * as {{PATH}} */
    $PATH = "";
    /** @global array $current_route_meta contains the discovered route's metadata */
    $current_route_meta = $ROUTER->discover_route();

    benchmark_end("router_setup");
    benchmark_start("context_setup");
    $context_processor->_stage_route_discovered(...$current_route_meta);
    $context_processor->_stage_bootstrap['_stage_route_discovered'] = true;
    
    // Assign some stuff to be done globally in your app.
    $global_route = __APP_ROOT__ . "/private/global_route.php";
    if (file_exists($global_route)) require_once $global_route;
    
    benchmark_end("context_setup");
    benchmark_start("controller_execution");

    $router_result = $ROUTER->execute_route();
    $context_processor->_stage_execute($router_result);
    $context_processor->_stage_bootstrap['_stage_execute'] = true;

    $context_result = $context_processor->_stage_output($router_result);
    $context_processor->_stage_bootstrap['_stage_output'] = true;
    ob_flush(); // Write the output buffer to the client
} catch (Exceptions\HTTP\HTTPException $e) {
    ob_clean(); // Clear the output buffer
    $context_result = $context_processor->_public_exception_handler($e);
} catch (Exception $e) {
    ob_clean();
    $context_result = $context_processor->_public_exception_handler(new \Exceptions\HTTP\UnknownError($e->getMessage()));
} catch (Error $e) {
    ob_clean();
    $context_result = $context_processor->_public_exception_handler(new \Exceptions\HTTP\UnknownError($e->getMessage()));
}

benchmark_end("controller_execution");
ob_clean();
// Let's finally output the result:
if($context_result !== null) {
    echo $context_result;
    $BENCHMARK_RESULTS['env_invoke'][DB_BENCH_END] = microtime(true) * 1000;
    $BENCHMARK_RESULTS['env_invoke'][DB_BENCH_DELTA] = $BENCHMARK_RESULTS['env_invoke']['end'] - $BENCHMARK_RESULTS['env_invoke']['start'];

    $global_benchmarks = "";
    if(app('debug') && isset($context_processor->encoding_mode) && $context_processor->encoding_mode === "text/html") {
        if($TIME_TO_UPDATE) $global_benchmarks .= "<script>console.warn('Cobalt Engine Bootstrap Completed')</script>";
        $global_benchmarks .= view("/debug/benchmarks.html",['results' => str_replace("\"","\\\"",json_encode($BENCHMARK_RESULTS))]);
        echo $global_benchmarks;
    }
    ob_flush();
    exit;
} else {
    kill("No content in buffer");
}
