<?php

namespace Cobalt\Routing\Kernels;

use Cobalt\Routing\Interfaces\ExecutionResult;
use Cobalt\Routing\Route;
use Cobalt\Routing\Router;
use Throwable;

interface KernelInterface {
    function initialize(Router $router):void;
    function onRouteDiscovered(Route $routeDetails):void;
    function onExecute(ExecutionResult &$routerResult):void;
    function output(ExecutionResult &$routerResult):mixed;
    function onThrowable(Throwable $throwable):mixed;

    function hasPermission():bool;
}
