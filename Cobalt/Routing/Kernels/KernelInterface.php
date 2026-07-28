<?php

namespace Cobalt\Routing\Kernels;

use Cobalt\Routing\Route;
use Cobalt\Routing\Router;
use Throwable;

interface KernelInterface {
    function initialize(Router $router):void;
    function onRouteDiscovered(Route $routeDetails):void;
    function onExecute(mixed &$routerResult):void;
    function output(mixed &$routerResult):mixed;
    function onThrowable(Throwable $throwable):mixed;

    function hasPermission():bool;
}
