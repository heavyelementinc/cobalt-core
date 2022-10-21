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
    public function _stage_init($context_meta);

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
    public function _stage_route_discovered($route, $directives);

    /**
     * Called after the route controller has been executed.
     * 
     * This method handles executing/updating the current state of the handler
     * after route execution.
     * 
     * @param $router_result - the return value of the route controller
     * @return void - this method should write to the output
     */
    public function _stage_execute($router_result);

    /**
     * Called at the end of the context.php
     * 
     * Prepare the context_output for output to client
     * 
     * @param $router_result - the return value of the route controller
     * @deprecated - use _stage_execute instead
     * @return void - this method should write to the output
     */
    public function _stage_output($context_result);

    /**
     * Called when an Exception\HTTP\* error is thrown.
     * 
     * This public exception handler is meant to make the exception handling
     * more sane.
     * 
     * @param object $error - The error object as thrown
     */
    public function _public_exception_handler($error);
}
