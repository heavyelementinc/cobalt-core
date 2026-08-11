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

use Cobalt\Auth\Users\Authentication;
use Cobalt\Customization\CustomizationManager;
use Cobalt\Routing\Route;
use Cobalt\Routing\Router;
use Exceptions\HTTP\Unauthorized;

benchmark_start("router_setup");
ob_start();

$CSP = [
    'frame-ancestors' => config()['CSP_allowed_frame_ancestors'],
    'font-src'        => config()['CSP_allowed_font_origins'],
    'script-src'      => config()['CSP_allowed_script_origins'],
    'script-src-elem' => __APP_SETTINGS__['CSP_allowed_script_elem_origins'] ?? '',
    'srcipt-src-attr' => [],
];

$ROUTER = new Router();
$route_context = $ROUTER->getRouterContext($_SERVER['REQUEST_URI']);
$route_details = $ROUTER->getCurrentContextDetails();

// $route_context = Routes\Route::get_router_context($_SERVER['REQUEST_URI']);
if(getenv('HTTP2')) {
    require_once __ENV_ROOT__ . "/globals/http2.php";
}

try {
    /** @global $auth Access the Authentication class */
    $auth = new Authentication();
    $auth->restoreSession();
} catch (Exception $e) {
    kill($e->getMessage());
}

$kernel = $ROUTER->getKernel();
if($kernel->hasPermission() === false) {
    throw new Unauthorized("Failed authorization.");
}

define("__APP_CONTEXT__", __APP_ROOT__ . "/app_context.php");
if(file_exists(__APP_CONTEXT__)) require_once __APP_CONTEXT__;


$context_result = null;
try {
    // // Check if we need to initialize Cobalt and start initialization if needed.
    // // When we init, we change the route_context to "init" so as to ignore all
    // // other web routes.
    // $init_file = __APP_ROOT__ . "/ignored/init";

    // // Check the settings to see if user accounts are enabled, and then check if we
    // // have set the current file.
    // if ($route_context === "web" && app("Auth_user_accounts_enabled") && !file_exists($init_file)) {
    //     require_once __ENV_ROOT__ . "/globals/init.php";
    // }

    // Create the routing table for the current context so that the Cobalt init
    // script has something to bind its routes to.
    $ROUTER->loadRoutes();

    $kernel->initialize($ROUTER);

    /** @global string PATH contains either an empty string the URI ends in '/'
     * or "../" if the URI ends without '/' also available in rendering engine 
     * as {{PATH}} */
    $PATH = "";
    /** @global Route $current_route_meta contains the discovered route's metadata */
    $currentRoute = $ROUTER->discoverRoute($_SERVER['REQUEST_URI']);

    benchmark_end("router_setup");
    benchmark_start("context_setup");
    $kernel->onRouteDiscovered($currentRoute);
    
    // Assign some stuff to be done globally in your app.
    $global_route = __APP_ROOT__ . "/private/global_route.php";
    if (file_exists($global_route)) require_once $global_route;
    
    benchmark_end("context_setup");
    benchmark_start("controller_execution");

    $router_result = $ROUTER->execute();
    $kernel->onExecute($router_result);

    $context_result = $kernel->output($router_result);
    
    ob_flush(); // Write the output buffer to the client
} catch (Exceptions\HTTP\HTTPException $e) {
     ob_clean(); // Clear the output buffer
    $http_version = $e->getHttpVersion() ?? "1.0";
    $status_code = $e->getStatusCode() ?? "500";
    $name = $e->getStatusName() ?? "Internal Server Error";
    $header = "HTTP/$http_version $status_code $name";
    header($header);
    $context_result = $kernel->onThrowable($e);
} catch (Throwable $e) {
    ob_clean();
    $header = "HTTP/1.0 500 Internal Server Error";
    header($header);
    $context_result = $kernel->onThrowable($e);
}

benchmark_end("controller_execution");
ob_clean();
// Let's finally output the result:
if($context_result !== null) {
    if(__APP_SETTINGS__['Enable_Content_Security_Policy_Nonce']) {
        csp_flush();
    }
    echo $context_result;
    $BENCHMARK_RESULTS['env_invoke'][DB_BENCH_END] = microtime(true) * 1000;
    $BENCHMARK_RESULTS['env_invoke'][DB_BENCH_DELTA] = $BENCHMARK_RESULTS['env_invoke']['end'] - $BENCHMARK_RESULTS['env_invoke']['start'];

    $global_benchmarks = "";
    if(app('debug') && isset($context_processor->encoding_mode) && $context_processor->encoding_mode === "text/html") {
        if($TIME_TO_UPDATE) $global_benchmarks .= "<script ".nonce().">console.warn('Cobalt Engine Bootstrap Completed')</script>";
        $global_benchmarks .= view("/debug/benchmarks.html",['results' => str_replace("\"","\\\"",json_encode($BENCHMARK_RESULTS))]);
        echo $global_benchmarks;
    }
    ob_flush();
    exit;
} else {
    kill("No content in buffer");
}
