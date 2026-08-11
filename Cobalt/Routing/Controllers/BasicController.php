<?php

namespace Cobalt\Routing\Controllers;

use Closure;
use Cobalt\Routing\Enums\HttpMethods;
use Cobalt\Routing\Interfaces\ControllerInterface;
use Cobalt\Routing\Interfaces\Routeable;
use Cobalt\Routing\Results\ExecutionResult;
use Cobalt\Routing\Route;
use Cobalt\Routing\Router;
use Override;

abstract class BasicController implements ControllerInterface {
    public private(set) ExecutionResult $executionResult;
    function initExecutionResult():ExecutionResult {
        $this->executionResult = new ExecutionResult();
        return $this->executionResult;
    }
    function getExecutionResult():ExecutionResult {
        return $this->executionResult;
    }

    abstract function index():mixed;
    
    #[Override]
    public static function get(string $path, string|Closure $method): Route {
        return Router::registerRoute(static::class, HttpMethods::GET, $path, $method);
    }

    #[Override]
    public static function head(string $path, string|Closure $method): Route {
        return Router::registerRoute(static::class, HttpMethods::HEAD, $path, $method);
    }

    #[Override]
    public static function post(string $path, string|Closure $method): Route {
        return Router::registerRoute(static::class, HttpMethods::POST, $path, $method);
    }

    #[Override]
    public static function put(string $path, string|Closure $method): Route {
        return Router::registerRoute(static::class, HttpMethods::PUT, $path, $method);
    }

    #[Override]
    public static function delete(string $path, string|Closure $method): Route {
        return Router::registerRoute(static::class, HttpMethods::DELETE, $path, $method);
    }
}
