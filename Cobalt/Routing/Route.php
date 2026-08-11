<?php

namespace Cobalt\Routing;

use Cobalt\Routing\Interfaces\ControllerInterface;
use Closure;
use Cobalt\Routing\Enums\HttpMethods;
use Cobalt\Routing\Interfaces\Routeable;
use Cobalt\Routing\Paths\RoutePath;
use Exception;
use Override;

/**
 * @phpstan-type RouteOptions array{'original_path':string,'real_path': string,'real_regex': string,,'context': string,'context_root': string,'controller': string,'uri_var_names': string,'uri_var_types': string,,'handler': string,'handler_data': string,'headers': string,'sitemap': string,'permission': string,'groups': string,'anchor': string,'navigation': string,'header_nav': string,'view': string,'view_args': string,'panel_name': string,'route_file': string,'csrf_required': string,'cache_control': string,'unread': string,'require_session': string,'nat_order': string}
 * @package Cobalt\Routing
 */
class Route implements Routeable {
    /**
     * Used to establish the route's path information
     * @var RoutePath
     */
    readonly RoutePath $path;
    
    public private(set) ?string $controller = null;

    /** @var array<int,string> */
    public array $uri_vars = [];
    public array $var_names = [];
    public private(set) array $uri_var_names = [];
    public private(set) array $uri_var_types = [];
    
    public private(set) ?string $context = null;
    public private(set) ?string $context_prefix = null;

    public private(set) ?HttpMethods $method = null;
    
    public private(set) string|Closure|null $backend_handler = null;
    public private(set) ?string $frontend_handler = null;
    public private(set) array $frontend_handler_data = [];
    
    public private(set) Closure|null $headers = null;
    public private(set) Closure|null $sitemap = null;
    public private(set) array $permission = [];

    public private(set) ?string $navigation = null;
    
    public private(set) ?string $panel_name = null;
    public private(set) ?string $route_file = null;
    public private(set) bool $csrf_required = false;
    public private(set) ?string $cache_control = null;

    public private(set) ?string $require_session = null;
    public private(set) ?string $nat_order = null;

    function __construct(null|string|ControllerInterface $controller, HttpMethods $httpMethod, string $path, string|Closure $backend_handler){
        $file = null;
        if (app("enable_debug_routes")) {
            $backtrace = debug_backtrace();
            $file = $backtrace[1]['file'] . " - Line " . $backtrace[1]['line'];
            $file = str_replace([__APP_ROOT__, __ENV_ROOT__], ["__APP_ROOT__", "__ENV_ROOT__"], $file);
        }
        $this->route_file = $file;
        $this->path = new RoutePath($this);
        $this->setPath($path);
        $this->setController($controller);
        $this->setHttpMethod($httpMethod);
        $this->setBackendHandler($backend_handler);
    }


    public function setOptions(array $options) {
        // $this->options = $options;
        throw new Exception("Not implemented");
    }

    #[Override]
    public function toArray(): array {
        return [

        ];
    }

    /**
     * 
     * @param array{'path':string,'original_path':string,'controller':string,'} $array 
     * @return Routeable 
     */
    #[Override]
    static function fromArray(array $array): Routeable {
        $route = new static(
            $array['controller'],
            HttpMethods::from(strtolower($array['method'])),
            $array['path'] ?? $array['original_path'],
            $array['handler']
        );
        $route->setOptions($array);
        return $route;
    }
    
    public function setPath(string $path) {
        $this->path->setPath($path);
    }

    public function setContext(string $context, array $details):void {
        $this->path->setContext($context, $details);
    }

    /* ======= Route Getters/Setters ======= */
    public function setController(string|ControllerInterface $controller):self {
        $this->controller = $controller;
        
        return $this;
    }

    public function setHttpMethod(HttpMethods $method):self {
        $this->method = $method;
        return $this;
    }

    public function setBackendHandler(string|Closure $backend_handler):self {
        $this->backend_handler = $backend_handler;
        return $this;
    }

    public function setNaturalOrder(int $order):self { 
        $this->nat_order = $order;
        return $this;
    }

}
