<?php

namespace Routes;

use Exceptions\HTTP\Unauthorized;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

class Route {

    public static $preg_quote = "[^/?]+";

    /** Register a GET route for the site. Valid tokens are {some_name}, ..., and ?
     * optional parameters.
     * 
     * $pattern = "/some/path/{token}/..." which would match "/some/path/variable/other/path"
     * 
     * @param string|Options $pattern A REQUEST_URI to be matched against using Cobalt's route syntax
     * @param string $controller A controller/method pair in the "Controller@method" format
     * @param array{handler: string, 
     *   permission: string, 
     *   groups: string, 
     *   anchor: {
     *      name: string,
     *      href: string,
     *      icon: string,
     *      order: int,
     *      attributes: array
     *   },
     *   navigation: {
     *      name: string,
     *      href: string,
     *      icon: string,
     *      order: int,
     *      attributes: array
     *   },
     *   csrf_required: bool,
     *   sitemap: {
     *      ignore: bool,
     *      children: callable,
     *      lastmod: callable
     *   }
     * } $options
     */
    static function get(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'get');
        Route::add_route($pattern, $controller, $options, 'get');
    }

    static function post(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'post');
        Route::add_route($pattern, $controller, $options, 'post');
    }

    static function put(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'put');
        Route::add_route($pattern, $controller, $options, 'put');
    }

    static function delete(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'delete');
        Route::add_route($pattern, $controller, $options, 'delete');
    }

    static function s_get(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'get');
        Route::add_route($pattern, $controller, array_merge($options, [
            'csrf_required' => $options['csrf_required'] ?? app("Router_csrf_required_default"),
            'require_session' => true, 
        ]), 'get');
    }

    static function s_post(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'post');
        Route::add_route($pattern, $controller, array_merge($options, [
            'csrf_required' => $options['csrf_required'] ?? app("Router_csrf_required_default"),
            'require_session' => true, 
        ]), 'post');
    }

    static function s_put(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'put');
        Route::add_route($pattern, $controller, array_merge($options, [
            'csrf_required' => $options['csrf_required'] ?? app("Router_csrf_required_default"),
            'require_session' => true, 
        ]), 'put');
    }

    static function s_delete(String|Options $pattern,$controller = "",array|BSONArray|BSONDocument $options = []) {
        if($pattern instanceof Options) return Route::add_route_from_option($pattern, 'delete');
        Route::add_route($pattern, $controller, array_merge($options, [
            'csrf_required' => $options['csrf_required'] ?? app("Router_csrf_required_default"),
            'require_session' => true, 
        ]), 'delete');
    }

    static function throw_without_session($access = "modify") {
        if(session_exists() === false) throw new Unauthorized("You're not authorized to $access this resource.");
    }

    /** Adding a route is simple. We need to parse the path and convert it to
     * standard REGEX, parse out the uri variable names and store them for later use.
     * 
     * You might be asking "why parse out the variable names here?" Well, eventually
     * we'd like to add a ROUTE TABLE CACHE to speed things up a bit and here seems
     * like the best place to find and store the variable names.
     */
    static function add_route(String $path, $controller, array|BSONArray|BSONDocument $options = [], $type = "get") {
        if($options instanceof BSONArray || $options instanceof BSONDocument) {
            $options = $options->getArrayCopy();
        }

        if($options instanceof Options) {
            $options = $options->jsonSerialize();
        }

        /** Okay, let's first suss out our variable names */
        $var_names = [];
        $search = "%\{(" . self::$preg_quote . ")\}%";
        preg_match_all($search, $path, $var_names);
        /** Now check for name collisions in our path definition. 
         * We count the number of vars and the number after performing an array unique,
         * if they're different, we know there was a name collision.
         * */
        // if(count($var_names) !== count(array_unique($var_names))) { 
        //     /** Trigger a warning */
        //     \trigger_error("URI variable name collision detected!",E_USER_WARNING);
        // }

        // Convert the path to a regex
        $regex = Route::convert_path_to_regex_pattern($path);

        // If the client handler is set, we should get that handler
        $handler_data = null;
        if (!key_exists('handler', $options)) $options['handler'] = null;
        else $handler_data = Route::get_js_handler($options['handler'], $regex, $controller);
        $router_table_address = $GLOBALS['ROUTE_TABLE_ADDRESS'];
        $context_permission = __APP_SETTINGS__['context_prefixes'][$router_table_address]['permission'] ?? null;
        // $context_permission = ($GLOBALS['permission_needed'] !== false) ? $GLOBALS['permission_needed'] : null;

        $file = null;
        if (app("enable_debug_routes")) {
            $backtrace = debug_backtrace();
            $file = $backtrace[1]['file'] . " - Line " . $backtrace[1]['line'];
            $file = str_replace([__APP_ROOT__, __ENV_ROOT__], ["__APP_ROOT__", "__ENV_ROOT__"], $file);
        }

        $path_prefix = app('context_prefixes')[$router_table_address]['prefix'];

        $real_path = substr($path_prefix ?? "",0,-1) . $path;
        $real_regex = Route::convert_path_to_regex_pattern($real_path);

        if (isset($options['anchor']) && !isset($options['anchor']['href'])) {
            if ($type === "get" && count($var_names[1]) !== 0) throw new \Exception("You must specify an href value in the anchor key for any GET route using variables.");
            $options['anchor']['href'] = $path;
        }

        $cache_control = [
            'disallow' => false,
            'max-age' => '604800',
            'type' => 'private',
        ];
        $nat_order = count($GLOBALS['ROUTE_TABLE'][$router_table_address][$type]);

        /** Store our route data in the full route table. */
        $GLOBALS['ROUTE_TABLE'][$router_table_address][$type][$regex] = [
            // Original pathname
            'original_path' => $path,     // The path defined by the Route::<method> argument (no root context!)
            'real_path' => $real_path,    // The real path includes the root context (/admin, etc)
            'real_regex' => $real_regex,  // The regex for the path "%^\/admin\/project\/?"
            'context' => $router_table_address, // The context type
            'context_root' => substr($path_prefix ?? "",0,-1), // The context root path

            // The PHP controller name
            'controller' => $controller,  // The controller "ProjectAdmin@newProject"

            // The var names that will be parsed out of the URI
            'uri_var_names' => $var_names[1],  // The names of variables found in the route
            'uri_var_types' => [], // Unused? 

            // The client-side controller (in JavaScript)
            'handler'    => $options['handler'],
            'handler_data'  => $handler_data, // Handler script data

            // The sitemap directives
            'sitemap'    => $options['sitemap'] ?? [], // array_merge([
            //     'ignore' => false, // Whether the sitemap should ignore this route
            //     'children' => fn () => '', // A delta function that returns a string of valid <url> entries
            //     'lastmod' => fn () => null, // A delta function which returns `Y-m-d` formatted string to indicate the last modification date, otherwise it uses the date the controller file was modified
            // ],  ?? []),

            // Permission for a page or API 
            'permission' => $options['permission'] ?? $context_permission ?? null,
            'groups'     => $options['group'] ?? null,

            // Directory stuff
            'anchor' => $options['anchor'] ?? [], // Keys: 'label', 'href', 'attributes'
            'navigation' => $options['navigation'] ?? [], // INDEXED array

            // Header stuff -- May contain keys: 'label', 'href', 'attributes'
            'header_nav' => $options['header_nav'] ?? null,

            // Admin panel name
            'panel_name' => $options['name'] ?? null,
            'route_file' => $file,   // The route.php file and the line number it was defined on!
            
            // API authentication stuff
            'csrf_required' => $options['csrf_required'] ?? $options['requires_csrf'] ?? false,
            
            // Cache Control stuff is only honored by API page requests
            'cache_control' => array_merge($options['cache_control'] ?? [], $cache_control),
            'unread' => $options['unread'] ?? false,
            'require_session' => $options['require_session'] ?? !app("Web_normally_open_pages"),

            // Info that route groups need
            'nat_order' => $nat_order,
        ];
        
        if(!key_exists($controller, $GLOBALS['ROUTE_LOOKUP_CACHE'])) {
            $GLOBALS['ROUTE_LOOKUP_CACHE'][$controller] = $real_path;
        }

        if(!empty($GLOBALS['ROUTE_TABLE'][$router_table_address][$type][$regex]['navigation'])) self::map_route_groups($GLOBALS['ROUTE_TABLE'][$router_table_address][$type][$regex]);
    }

    static function add_route_from_option(Options $route, string $method) {
        global $ROUTE_TABLE;
        global $ROUTE_TABLE_ADDRESS;
        $route->set_context($ROUTE_TABLE_ADDRESS);

        $file = null;
        if (app("enable_debug_routes")) {
            $backtrace = debug_backtrace();
            $file = $backtrace[1]['file'] . " - Line " . $backtrace[1]['line'];
            $file = str_replace([__APP_ROOT__, __ENV_ROOT__], ["__APP_ROOT__", "__ENV_ROOT__"], $file);
        }

        $regex = $route->get_regex();
        $nat_order = count($GLOBALS['ROUTE_TABLE'][$ROUTE_TABLE_ADDRESS][$method]);
        $controller = $route->get_controller();
        $real_path = $route->get_real_path();
        $details = [
            // Request
            'original_path' => $route->get_path(),
            'real_path' => $real_path,
            'real_regex' => $regex,
            'uri_var_names' => $route->get_var_names(),
            'context' => $route->get_context(),
            'context_root' => $route->get_context_root(),
            
            // Fulfillment
            'controller' => $controller,
            
            // Client
            'handler' => $route->get_handler(),
            'handler_data' => '', // Unused?
            'sitemap ' => $route->get_sitemap(),
            'navigation' => $route->get_navigation(),
            'cache_control' => $route->get_cache_control(),
            'unread' => $route->get_unread(),
            
            // Debug
            'nat_order' => $nat_order,
            'route_file' => $file,

            // Security
            'permission' => $route->get_permission(),
            'groups' => $route->get_groups(),
            'csrf_required' => $route->get_csrf_required(),
            'require_session' => $route->get_require_session(),
        ];
        
        $ROUTE_TABLE[$ROUTE_TABLE_ADDRESS][$method][$regex] = $details;

        if(!key_exists($controller, $GLOBALS['ROUTE_LOOKUP_CACHE'])) {
            $GLOBALS['ROUTE_LOOKUP_CACHE'][$controller] = $real_path;
        }

        if(!empty($GLOBALS['ROUTE_TABLE'][$ROUTE_TABLE_ADDRESS][$method][$regex]['navigation'])) self::map_route_groups($GLOBALS['ROUTE_TABLE'][$ROUTE_TABLE_ADDRESS][$method][$regex]);
    }

    static function map_route_groups(&$value) {
        global $ROUTE_GROUPS;
        if(!$ROUTE_GROUPS) $ROUTE_GROUPS = [];

        foreach($value['navigation'] as $index => $navItem) {
            $group = $navItem;
            if(gettype($group) === "array") $group = $index;
            if(!key_exists($group,$ROUTE_GROUPS)) $ROUTE_GROUPS[$group] = [];
            $ROUTE_GROUPS[$group][$value['real_regex']] = &$value;
        }
    }

    /** Expressions allowed in a route path:
     *     {name} - Variable name, the name can be anything enclosed in { }
     *               variables will be stored in $_GET, name collisions in 
     *     
     *     ...    - A placeholder token for 1 or more items in the pathname
     * 
     *     ?      - 0 or 1 of the preceeding character or token
     */
    static function convert_path_to_regex_pattern($route) {
        $preg_quote = self::$preg_quote;
        $regex_search = "%\{$preg_quote\}%";
        $regex_replace = "($preg_quote)";
        $new_route = preg_replace($regex_search, $regex_replace, $route);

        // Make routes with optional parameters tolerant to a missing trailing slash
        $new_route = str_replace("/$regex_replace?", "/?$regex_replace?", $new_route);

        // Finally, we create our regex pattern
        $new_route = "%^" . str_replace(["/", "..."], ["\/", "(.*)"], $new_route);



        // Make the route tolerant of trailing slashes
        if (substr($new_route, -2) === "\/") {
            $new_route .= "?";
        } else {
            $new_route .= "\/?";
        }
        return "$new_route$%";
    }

    static function get_js_handler($handler, $path, $controller) {
        return "";
    }

    static function get_router_context($request_uri) {
        // Remove the query string
        $request_uri = str_replace("?" . $_SERVER['QUERY_STRING'], "", $request_uri);

        // The default context is web
        $context = "web";
        $endpoints = app("context_prefixes"); // Get our API endpoints

        /** Determine if the admin panel should be an available router context */
        if (!app("Admin_panel_access") && isset($endpoints['admin'])) unset($endpoints['admin']);

        foreach ($endpoints as $context_name => $api) {
            // Check if our request is in the list of APIs
            if (substr($request_uri, 0, strlen($api['prefix'] ?? "")) === $api['prefix']) {
                $context = $context_name;
                break;
            }
        }

        return $context;
    }
}
