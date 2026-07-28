<?php

namespace Cobalt\Routing;

use Cobalt\Routing\Interfaces\Controller;
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
    
    protected ?Controller $controller = null;

    protected array $var_names = [];
    protected array $uri_var_names = [];
    protected array $uri_var_types = [];
    
    protected ?string $context = null;
    protected ?string $context_prefix = null;

    protected ?HttpMethods $method = null;
    
    protected string|Closure|null $backend_handler = null;
    protected ?string $frontend_handler = null;
    protected array $frontend_handler_data = [];
    
    protected Closure|null $headers = null;
    protected Closure|null $sitemap = null;
    protected array $permission = [];

    protected ?string $navigation = null;
    
    protected ?string $panel_name = null;
    protected ?string $route_file = null;
    protected bool $csrf_required = false;
    protected ?string $cache_control = null;

    protected ?string $require_session = null;
    protected ?string $nat_order = null;

    function __construct(?Controller $controller, HttpMethods $httpMethod, string $path, string|Closure $backend_handler){
        $file = null;
        if (app("enable_debug_routes")) {
            $backtrace = debug_backtrace();
            $file = $backtrace[1]['file'] . " - Line " . $backtrace[1]['line'];
            $file = str_replace([__APP_ROOT__, __ENV_ROOT__], ["__APP_ROOT__", "__ENV_ROOT__"], $file);
        }
        $this->route_file = $file;
        $this->path = new RoutePath();
        $this->path->setPath($path);
        $this->setController($controller);
        $this->setHttpMethod($httpMethod);
        $this->setBackendHandler($backend_handler);
        
    }

    public static function rt(Controller $controller, HttpMethods $httpMethod, string $path, string|Closure $handler): Routeable {
        $route = new static($controller, $httpMethod, $path, $handler);
        /** @var Router $ROUTER */
        global $ROUTER;
        $ROUTER->routes->addRoute($route);
        return $route;
    }

    #[Override]
    public static function get(Controller $controller, string $path, string|Closure $handler): Routeable {
        return static::rt($controller, HttpMethods::GET, $path, $handler);
    }

    #[Override]
    public static function post(Controller $controller, string $path, string|Closure $handler): Routeable {
        return static::rt($controller, HttpMethods::POST, $path, $handler);
    }

    #[Override]
    public static function delete(Controller $controller, string $path, string|Closure $handler): Routeable {
        return static::rt($controller, HttpMethods::DELETE, $path, $handler);
    }

    #[Override]
    public static function put(Controller $controller, string $path, string|Closure $handler): Routeable {
        return static::rt($controller, HttpMethods::PUT, $path, $handler);
    }

    #[Override]
    public static function head(Controller $controller, string $path, string|Closure $handler): Routeable {
        return static::rt($controller, HttpMethods::HEAD, $path, $handler);
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
    
    /* ======= Route Getters/Setters ======= */

    


    public function setController(string|Controller $controller):self {
        if(is_string($controller)) $controller = new $controller();
        $this->controller = $controller;
        return $this;
    }
    public function getController():Controller {
        return $this->controller;
    }

    public function setHttpMethod(HttpMethods $method):self {
        $this->method = $method;
        return $this;
    }
    public function getHttpMethod():HttpMethods {
        return $this->method;
    }


    public function setBackendHandler(string|Closure $backend_handler):self {
        $this->backend_handler = $backend_handler;
        return $this;
    }
    public function getBanckendHandler():string|Closure {
        return $this->backend_handler;
    }


}
