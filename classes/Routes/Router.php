<?php

/**
 * The Router
 * 
 * This class handles mapping URL paths to the corresponding functions/methods
 * in the router table.
 * 
 * @todo When the router parses the values, it should check for name collisions
 * between existing $_GET parameters and save them as $_GET["<name>"] or 
 * $_GET["uri_<name>"] if a collision exists. The "..." should be saved as a 
 * string called $_GET['uri_misc']
 * 
 * @todo Add typing so that digit:{varname} would be typecast to a digit or,
 * throws an BadRequest error if its no a digit
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Routes;

use Cobalt\Extensions\Extensions;
use Exception;
use Exceptions\HTTP\MethodNotAllowed;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\NotImplemented;
use Exceptions\HTTP\Unauthorized;

class Router {

    public $current_route = null;
    private $route_cache_name = "config/routes.json";
    public $router_table_list = [];
    public $route_context = "web";
    public $registered_plugin_controllers = [];
    private $router_table_initialized = false;
    private $router_table_loaded = false;
    public $routes = null;
    public $method = null;
    public $uri = null;
    public $context_prefix = null;
    public $cache_resource = null;

    /** Let's establish our $route_context and our method  */
    function __construct($route_context = "web", $method = null) {
        if ($method === null) $method = $_SERVER['REQUEST_METHOD'];
        $this->route_context = $route_context;
        $this->method = strtolower($method);
    }

    function init_route_table() {
        if($this->router_table_initialized) return;
        $contexts = app('context_prefixes');
        //  = array_fill_keys(array_keys($contexts), []);

        global $ROUTE_TABLE;
        
        foreach($contexts as $context => $data) {
            $ROUTE_TABLE[$context] = [
                'get'    => [],
                'post'   => [],
                'put'    => [],
                'delete' => [],
            ];

            $results = [];
            Extensions::invoke("register_routes", $context, $results);
            // Make a list of all the routes we need to load
            $this->router_table_list[$context] = [
                ...$results,
                __ENV_ROOT__ . "/routes/$context.php",
                __APP_ROOT__ . "/routes/$context.php",
                // __APP_ROOT__ . "/private/routes/$context.php",
            ];

        }

        $this->router_table_initialized = true;
        // array_push($this->router_table_list, __APP_ROOT__ . "/private/routes/" . $this->route_context . ".php");

    }

    function get_routes() {
        global $ROUTE_TABLE, $ROUTE_TABLE_ADDRESS;
        if($this->router_table_loaded) {
            $this->routes = $ROUTE_TABLE;
            return;
        }
        foreach($this->router_table_list as $context => $value) {
            foreach($value as $table){
                $ROUTE_TABLE_ADDRESS = $context;
                if(file_exists($table)) require_once $table;
            }
        }
        $this->routes = $ROUTE_TABLE;
        $this->router_table_loaded = true;
    }


    /** @todo complete this */
    function table_from_cache() {
        $this->cache_resource = new \Cache\Manager($this->route_cache_name);
        return false;
    }


    function discover_route($route = null, $query = null, $method = null, $context = null) {
        if ($route   === null) $route   = $_SERVER['REQUEST_URI'];
        if ($query   === null) $query   = $_SERVER['QUERY_STRING'];
        if ($method  === null) $method  = $this->method;
        if ($context === null) $context = $this->route_context;
        /** Let's remove the query string from the incoming request URI and decode 
         * any special characters in our URI.
         */
        $this->uri = urldecode(str_replace(["?" . $query], "", $route));
        if ($context !== "web") {
            $this->context_prefix = app("context_prefixes")[$context]['prefix'];
            $this->uri = substr($this->uri, strlen($this->context_prefix) - 1);
        }

        // $route = null;
        /** Search through our current routes and look for a match */
        foreach ($this->routes[$context][$method] as $preg_pattern => $directives) {
            $match = [];
            /** Regular Expression against our uri, store any matches in $match */
            if (preg_match($preg_pattern, $this->uri, $match) === 1) {
                if ($match !== null) $this->set_uri_vars($directives, $match, $preg_pattern, $context);

                $this->current_route = $preg_pattern;
                if ($route[strlen($route) - 1] === "/") {
                    $GLOBALS['PATH'] = "../";
                }
                return [$preg_pattern, $directives];
            }
        }
        if ($this->current_route === null) throw new NotFound("No route discovered.");
    }

    function set_uri_vars($directives, $match, $route, $context) {
        if($context === null) $context = $this->route_context;
        array_shift($match);
        $_GET['uri'] = \array_fill_keys($directives['uri_var_names'], $match);
        foreach ($directives['uri_var_names'] as $i => $name) {
            if (key_exists($name, $_GET)) $name = "uri_$name";
            if (key_exists($i, $match)) $_GET['uri'][$name] = $match[$i];
        }
        $this->routes[$context][$this->method][$route]['matches'] = $match;
        // array_shift($match);
        // if($match === null) $match = [];
        // /** Store the $match with the route data */
        // $this->routes[$this->method][$route]['matches'] = $match;
        // $this->current_route = $route;
    }

    function execute_route($route = null, $method = null, $context = null) {
        // Allow executing arbitrary routesp
        if($route   === null) $route   = $this->current_route;
        if($method  === null) $method  = $this->method;
        if($context === null) $context = $this->route_context;

        /** Store our route data for easy access */
        $exe = $this->routes[$context][$method][$route];
        if(!$exe) throw new MethodNotAllowed("Route controller not implemented");
        if (isset($exe['permission'])) {
            $permission = true;
            try {
                $permission = $GLOBALS['auth']->has_permission($exe['permission']);
            } catch (\Exceptions\HTTP\Unauthorized $e) {
                $permission = false;
            }
            $unauthorized = "\\Exceptions\\HTTP\\Unauthorized";
            if (!$permission) throw new $unauthorized('You do not have the required privileges.');
        }
        if ($exe['require_session'] === true) {
            $errorMessage = "modify";
            if($method === "get") $errorMessage = "access";
            
            $contexts = app('context_prefixes');

            $unauthorized = "\\Exceptions\\HTTP\\Unauthorized";
            if(key_exists("no_session_exception", $contexts[$this->route_context])) $unauthorized = $contexts[$this->route_context]["no_session_exception"];
            if(!session_exists()) throw new $unauthorized("You do not have permission to $errorMessage this resource");
        }
        /** Check if we're a callable or a string and execute as necessary */
        if (is_callable($exe['controller'])) throw new \Exception("Anonymous functions are no longer supported as controllers."); //return $this->controller_callable($exe);

        if (is_string($exe['controller'])) return $this->controller_string($exe);
    }

    function controller_callable($exe) {
        return $ctrl = $exe['controller'](...$exe['matches']);
    }

    function controller_string($exe) {
        // Split our Pages@method string
        $explode = explode("@", $exe['controller']);
        $controller_name = $explode[0];
        $controller_method = $explode[1];

        $controller_search = [
            __APP_ROOT__ . "/controllers",
            __APP_ROOT__ . "/private/controllers",
            // ...array_values($this->registered_plugin_controllers),
            __ENV_ROOT__ . "/controllers"
        ];

        extensions()::invoke("register_controller_dir", $controller_search);

        try {
            // We are doing these in reverse order because we want our app's 
            // controllers to override the core's controllers.
            $controller_file = find_one_file($controller_search, "$controller_name.php");
            if(!$controller_file) die("Controller not found");
        } catch (\Exception $e) {
            // throw new NotImplemented("Controller $controller_name not found.");
            // header("HTTP/")
            die("Controller $controller_name not found.");
        }

        // We need to require this because the controllers folder is outside of our 
        // classes path and the developer is going to be able to create new controllers
        require_once $controller_file;

        // Instantiate our controller and then execute it
        $ctrl = new $controller_name();
        if (!method_exists($ctrl, $controller_method)) throw new \Exceptions\HTTP\MethodNotAllowed("Specified method was not found.");
        $test = new \ReflectionMethod($controller_name, $controller_method);
        /** We check to make sure that we're not going to have callable exception 
         * where we aren't supplying the correct number of arguments to a callable. 
         * So we check how many arguments are required for the callable and then 
         * count the number of matches we found to ensure that there will always be 
         * enough matches.
         * 
         * Otherwise, we'll throw a 400 Bad Request.
         */
        if ($test->getNumberOfRequiredParameters() > count($exe['matches'])) throw new \Exceptions\HTTP\BadRequest("Method supplied too few arguments.");
        if (gettype($exe['matches']) !== "array") $exe['matches'] = [$exe['matches']];
        
        /** Execute our method */
        return $ctrl->{$controller_method}(...$exe['matches']);
        
    }

    public $router_js_table = [
        __APP_ROOT__ . "/controllers/client/",
        __APP_ROOT__ . "/private/controllers/client/",
        __ENV_ROOT__ . "/controllers/client/",
    ];

    /** @todo Fix terrible nested loops/logic */
    function get_js_route_table() {
        $table = [];
        $prefix = "";
        if ($GLOBALS['route_context']) $prefix = "^" . app("context_prefixes")[$GLOBALS['route_context']]['prefix'];
        $prefix = substr($prefix, 0, -1);

        extensions()::invoke("register_client_controllers",$this->router_js_table);

        foreach($this->routes as $context => $methods) {
            foreach ($methods as $method => $routes) {
                foreach ($routes as $path => $route) {
                    $handler = $route['handler'];
                    $hasHandler = false;
                    if($handler) $hasHandler = true;
                    if($hasHandler === false) {
                        $handlerByControllerName = "$route[controller].js";
                        $handler = $handlerByControllerName;
                        $hasHandler = true;
                    }
                    if($hasHandler === false) continue;

                    if(isset($route['handler'])) {
                        $file = find_one_file($this->router_js_table, $route['handler']);
                        if(!$file) throw new Exception("The router table specfied a client controller but the file was not found");

                        $real_regex = $route['real_regex'];
                        // if ($prefix !== "" && $path[0] == "^") $real_regex = substr($real_regex, 2);
                        $index1 = 0;
                        $index2 = 0;
                        if ($real_regex[0] === "%") $index1 = 1;
                        if ($real_regex[strlen($real_regex) - 1] === "%") $index2 = -1;
                        $real_regex = substr($real_regex, $index1, $index2);
                        array_push($table, "\n'$real_regex': " . file_get_contents($file));
                        continue;
                    }
                }
            }
        }
        return "\nconst router_table = {\n" . implode(",\n", array_unique($table)) . "\n}\n";
    }

    // public function get_route_path_by_controller_name($name,$controller,)
}
