<?php

namespace Cobalt\Routing;

use Closure;
use Cobalt\Routing\Enums\HttpMethods;
use Cobalt\Routing\Interfaces\ControllerInterface;
use Cobalt\Routing\Kernels\KernelInterface;
use Cobalt\Routing\Results\ExecutionResult;
use Cobalt\Routing\RouteList;
use DirectoryIterator;
use Exceptions\HTTP\NotFound;

class Router {
    const ROUTE_DIRECTORIES = [
        __ENV_ROOT__ . "/routes/",
        __APP_ROOT__ . "/routes/",
    ];

    const CONTEXT_FOUND = 1;
    const ROUTES_LOADED = 2;
    const ROUTE_DISCOVERED = 3;

    protected int $state = 0;
    readonly RouteList $routes;
    readonly string $contextFile;
    readonly array $contexts;
    private ?string $currentContext = null;
    public private(set) ?string $loadingContext = null;
    public private(set) ?string $loadingRouteFile = null;
    public private(set) ?Route $currentRoute = null;
    public private(set) ?ControllerInterface $controllerInstance = null;
    public private(set) array $boundaries = [];
    
    function __construct() {
        $this->routes = new RouteList();
        // Set up our contexts
        $this->contextFile = find_one_file([__APP_ROOT__."/config/", __ENV_ROOT__."/Cobalt/Routing/"], "contexts.php");
        $this->contexts = include $this->contextFile;
    }

    public function getRouterBoundaries():array {
        foreach($this->contexts as $ctx) {
            if($ctx['active'] ?? true === false) continue; // Skip inactive boundaries
            $trailing_slash = ($ctx['prefix'][strlen($ctx['prefix']) - 1] === "/") ? "?" : "";
            $this->boundaries["^".preg_quote($data['prefix'] ?? "")."$trailing_slash"] = $ctx['prefix'];
        }
        return $this->boundaries;
    }

    static function registerRoute(string $staticControllerName, HttpMethods $httpMethod, string $path, string|Closure $method):Route {
        $route = new Route($staticControllerName, $httpMethod, $path, $method);
        /** @var Router $ROUTER */
        global $ROUTER;
        $ROUTER->routes->addRoute($route);
        $route->setContext($ROUTER->currentContext, $ROUTER->contexts[$ROUTER->currentContext]);
        return $route;
    }

    function getRouterState():int {
        return $this->state;
    }

    function getRouterContext(string $request_uri):string {
        $request_uri = str_replace("?" . $_SERVER['QUERY_STRING'], "", $request_uri);
        $context = null;

        foreach($this->contexts as $name => $details) {
            if(!$this->doesRequestUriMatchPrefix($request_uri, $details)) continue;

            $context = $name;
            break;
        }

        if($context === null) {
            throw new NotFound("Failed to locate a known context");
        }

        $this->state |= self::CONTEXT_FOUND;
        $this->currentContext = $context;
        return $context;
    }

    private function doesRequestUriMatchPrefix(string $request_uri, array $details):bool {
        $pattern = sprintf('/^(%s)/',preg_quote($details['prefix'],'/'));//strlen($details['prefix']);
        return preg_match($pattern, $request_uri) === 1;
    }

    function getCurrentContextName():?string {
        return $this->currentContext;
    }

    function getCurrentContextDetails(?string $context = null):array {
        return $this->contexts[$context ?? $this->currentContext];
    }

    static function getContext():array {
        global $ROUTER;
        return $ROUTER->getCurrentContextDetails();
    }

    function loadRoutes() {
        foreach(self::ROUTE_DIRECTORIES as $dir) {
            if(!file_exists($dir) || !is_dir($dir)) continue;
            $route_directory = new DirectoryIterator($dir);
            foreach($route_directory as $route_item) {
                $this->loadIndividualRouteFiles($route_item);
            }
        }
        $this->loadingContext = null;
        $this->state |= self::ROUTES_LOADED;
    }

    private function loadIndividualRouteFiles(DirectoryIterator $dir) {
        // Check that we're not looking at a directory or a dotfile
        if($dir->isDir()) return;
        if($dir->isDot()) return;
        // Check if we're examining a PHP file. If not, skip it.
        if($dir->getExtension() !== "php") return;

        $context = $dir->getBasename(".php");
        if(!key_exists($context, $this->contexts)) return;
        if($this->contexts[$context]['active'] ?? false) return;
        $this->loadingContext = $context;
        $this->loadingRouteFile = $dir->getPathname();
        include $dir->getPathname();
    }

    function getKernel():KernelInterface {
        $kernel = $this->contexts[$this->currentContext]['kernel'];
        if(is_null($kernel)) {
            kill("Failed to locate a suitable kernel.");
        }
        $k = new $kernel();
        $k->initialize($this);
        return $k;
    }

    function discoverRoute(string $uri):Route {
        foreach($this->routes as $route) {
            if(!$route->path->matches($uri)) continue;
            $this->state |= self::ROUTE_DISCOVERED;
            $this->currentRoute = $route;
            $this->controllerInit();
            return $route;
        }
        throw new NotFound("Route not found");
    }

    function controllerInit() {
        $controller = $this->currentRoute->controller;
        
        /** @var ControllerInterface $controller */
        if(is_string($controller)) $controller = new $controller();
        $this->controllerInstance = $controller;
        $controller->initExecutionResult();
    }

    function execute():ExecutionResult {
        $method = $this->currentRoute->backend_handler;
        $uri_vars = $this->currentRoute->uri_vars;
        if(!method_exists($this->controllerInstance, $method)) {
            throw new NotFound("Failed to locate method");
        }
        
        $exec = $this->controllerInstance->getExecutionResult();

        $exec->setControllerResult(
            '@main_content@',
            $this->controllerInstance->{$method}(...$uri_vars)
        );

        return $exec;
    }
}
