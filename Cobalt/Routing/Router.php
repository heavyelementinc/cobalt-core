<?php

namespace Cobalt\Routing;

use Cobalt\Routing\Kernels\KernelInterface;
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
    
    function __construct() {
        $routes = new RouteList();
        // Set up our contexts
        $this->contextFile = find_one_file([__APP_ROOT__,__ENV_ROOT__], "\Cobalt\Routing\contexts.php");
        $this->contexts = include $this->contextFile;
    }

    function getRouterState():int {
        return $this->state;
    }

     function getRouterContext(string $request_uri):string {
        $request_uri = str_replace("?" . $_SERVER['QUERY_STRING'], "", $request_uri);
        $context = "web";

        $this->state |= self::CONTEXT_FOUND;
        return $context;
    }

    function getCurrentContextName():?string {
        return $this->currentContext;
    }

    function getCurrentContextDetails(?string $context = null):array {
        return $this->contexts[$context ?? $this->currentContext];
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

        $context = $dir->getBasename();
        if(!key_exists($context, $this->contexts)) return;
        if($this->contexts[$context]['active'] ?? false) return;
        $this->loadingContext = $context;
        $this->loadingRouteFile = $dir->getPathname();
        include $dir->getPathname();
    }

    function getKernel():KernelInterface {
        $kernel = $this->contexts[$this->currentContext]['kernel'];
        if(!is_a($kernel, KernelInterface::class)) {
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
            return $route;
        }
        throw new NotFound("Router not found");
    }

    function execute():mixed {
        // $this->currentRoute;
        return null;
    }
}
