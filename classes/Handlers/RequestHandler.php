<?php

/** RequestHandler
 * 
 * The RequestHandler interface is a way of defining multiple interfaces which 
 * can satisfy an incoming request from the web.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Handlers;

abstract class RequestHandler {
    public array $_stage_bootstrap;
    public string $encoding_mode;
    protected string $content_type = "text/html";
    protected string $allowed_origin = __APP_SETTINGS__['domain_name'];
    protected array $allowed_modes = ["https://", "http://"];
    public array $allowed_origins = __APP_SETTINGS__["API_CORS_allowed_origins"];
    public $http_mode = null;
    public $headers = null;
    public $method = null;

    function __construct() {
        $this->http_mode = (is_secure()) ? "https" : "http";
        $this->headers = apache_request_headers();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->allowed_modes = ["https://", "http://"];
    }

    /**
     * Called after the router has initialized
     * 
     * Use this method to prepare the interface for the discovered route in the
     * next stage.
     * 
     * @param $context_meta all relevant data regarding the current route context
     * @return void
     */
    abstract public function _stage_init($context_meta): void;

    /** 
     * Called after the router discovers the route
     * 
     * Use this to handle request validation or anything which depends on knowing
     * the router stage
     * 
     * @throws \Exceptions\HTTP
     * @param string $route - The actual route regex we've found
     * @param array $directives - The route 
     * @param bool $isOptions
     * @return bool
     */
    abstract public function _stage_route_discovered(string $route, array $directives, bool $isOptions): bool;

    /**
     * Called after the route controller has been executed.
     * 
     * This method handles executing/updating the current state of the handler
     * after route execution.
     * 
     * @param $router_result - the return value of the route controller
     * @return void - this method should write to the output
     */
    abstract public function _stage_execute($router_result): void;

    /**
     * Called at the end of the context.php
     * 
     * Prepare the context_output for output to client
     * 
     * @param $router_result - the return value of the route controller
     * @deprecated - use _stage_execute instead
     * @return mixed - return some value that is then processed and sent to the client
     */
    abstract public function _stage_output($context_result): mixed;

    /**
     * Called when an Exception\HTTP\* error is thrown.
     * 
     * This public exception handler is meant to make the exception handling
     * more sane.
     * 
     * @param object $error - The error object as thrown
     */
    abstract public function _public_exception_handler($error): mixed;

    // This might need some refactoring. Is HTTP_ORIGIN where we want to be 
    function cors_management($origin = null) {
        /** Set our allowed origin to be our app's domain name */
        $allowed_origin = app("domain_name");
        $allowed_methods = "OPTIONS, GET, POST, PUT, PATCH, DELETE";
        $current_origin = $origin ?? getHeader("referer", null, true, false) ?? $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) $current_origin = $this->url_to_current_mode($_SERVER['HTTP_X_FORWARDED_HOST']);
        $current_origin = parse_url($current_origin, PHP_URL_HOST);

        /** Check if our route allows us to ignore CORS */
        if (isset($GLOBALS['current_route_meta']['cors_disabled']) && $GLOBALS['current_route_meta']['cors_disabled']) {
            /** If it does, we send the origin back to as the allowed origin */
            $allowed_origin = $current_origin;
            /** TODO: Send the current route's method back, too. */
        } else if (app("API_CORS_enable_other_origins") && isset($current_origin)) {
            /** If HTTP_ORIGIN is set, we'll check if the origin is in our allowed origins and if not,
             * throw an unauthorized error */
            if (!in_array($current_origin, $this->allowed_origins)) $this->cors_error();
            /** Otherwise, we'll set our to the server origin, since its allowed */
            $allowed_origin = $current_origin;
        }

        $allowed_origin = $this->url_to_current_mode($allowed_origin);

        /** Now we'll throw the headers back to the client */
        header("Access-Control-Allow-Origin: $allowed_origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: $allowed_methods");
        $current_headers = headers_list();
        if(key_exists('Content-Type',$current_headers) || key_exists('content-type', $current_headers)) $this->content_type = $current_headers['Content-Type'] ?? $current_headers['content-type'];
        else header("Content-Type: " . $this->content_type);
    }

    function cors_error() {
        $origin = $this->url_to_current_mode(app('domain_name'));
        /** Throw the domain name back as a CORS header */
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Content-Type: " . $this->content_type);
        /** Throw an unauthorized error */
        throw new \Exceptions\HTTP\Unauthorized("Your origin was not recognized.");
    }

    function url_to_current_mode($url) {
        return "$this->http_mode://" . str_replace($this->allowed_modes, "", $url);
    }
}
