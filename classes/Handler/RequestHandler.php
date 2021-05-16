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

namespace Handler;

interface RequestHandler {

    /**
     * Called after the router has initialized
     * 
     * Use this method to prepare the interface for the discovered route in the
     * next stage.
     * 
     * @param $context_meta all relevant data regarding the current route context
     * @return void
     */
    public function stage_init($context_meta);

    /** 
     * Called after the router discovers the route
     * 
     * Use this to handle request validation or anything which depends on knowing
     * the router stage
     * 
     * @throws \Exceptions\HTTP
     * @param string $route - The actual route regex we've found
     * @param array $directives - The route 
     * @return bool
     */
    public function stage_route_discovered($route, $directives);

    /**
     * Called after the route controller has been executed.
     * 
     * This method outputs the final results of the request to the client.
     * 
     * @param $router_result - the return value of the route controller
     * @return void - this method should write to the output
     */
    public function stage_execute($router_result);

    /**
     * Called when an Exception\HTTP\* error is thrown.
     * 
     * This public exception handler is meant to make the exception handling
     * more sane.
     * 
     * @param string $message - A message string to be displayed
     * @param array $data - Additional data for the exception
     */
    public function public_exception_handler($message, $data = []);
}
