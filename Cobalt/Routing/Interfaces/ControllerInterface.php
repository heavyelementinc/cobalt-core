<?php

namespace Cobalt\Routing\Interfaces;

use Closure;
use Cobalt\Routing\Route;

interface ControllerInterface {
    function initExecutionResult():ExecutionResult;
    function getExecutionResult():ExecutionResult;
    static function get(string $path, string|Closure $method):Route;
    static function head(string $path, string|Closure $method):Route;

    static function post(string $path, string|Closure $method):Route;
    static function put(string $path, string|Closure $method):Route;
    static function delete(string $path, string|Closure $method):Route;
}
