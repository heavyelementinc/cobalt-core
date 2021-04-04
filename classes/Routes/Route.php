<?php
namespace Routes;
class Route{

    public static $preg_quote = "[^/?]+";

    /** Add a GET route to the site */
    static function get(String $path, $controller, array $additional = []){
        Route::add_route($path,$controller,$additional,'get');
    }

    static function post(String $path, $controller, array $additional = []){
        Route::add_route($path,$controller,$additional,'post');
    }

    static function put(String $path, $controller, array $additional = []){
        Route::add_route($path,$controller,$additional,'put');
    }

    static function delete(String $path, $controller, array $additional = []){
        Route::add_route($path,$controller,$additional,'delete');
    }

    /** Adding a route is simple. We need to parse the path and convert it to
     * standard REGEX, parse out the uri variable names and store them for later use.
     * 
     * You might be asking "why parse out the variable names here?" Well, eventually
     * we'd like to add a ROUTE TABLE CACHE to speed things up a bit and here seems
     * like the best place to find and store the variable names.
     */
    static function add_route(String $path, $controller, array $additional = [],$type = "get"){
        /** Okay, let's first suss out our variable names */
        $var_names = [];
        $search = "%\{(" . self::$preg_quote . ")\}%";
        preg_match_all($search,$path,$var_names);
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
        if(!key_exists('handler',$additional)) $additional['handler'] = null;
        else $handler_data = Route::get_js_handler($additional['handler'],$regex,$controller);
        $router_table_address = $GLOBALS['route_table_address'];
        /** Store our route data in the full route table. */
        $GLOBALS[$router_table_address][$type][$regex] = [
            'controller' => $controller,
            'handler'    => $additional['handler'],
            'handler_data'  => $handler_data,
            'csrf_required' => $additional['requires_csrf'] ?? app("Router_csrf_required_default"),
            'permission' => $additional['permission'] ?? null,
            'group'      => $additional['group'] ?? null,
            'original_path' => $path,
            'panel_name' => $additional['name'] ?? null,
            'uri_var_names' => $var_names[1],
            'uri_var_types' => []
        ];
    }

    /** Expressions allowed in a route path:
     *     {name} - Variable name, the name can be anything enclosed in { }
     *               variables will be stored in $_GET, name collisions in 
     *     
     *     ...    - A placeholder token for 1 or more items in the pathname
     * 
     *     ?      - 0 or 1 of the preceeding character or token
     */
    static function convert_path_to_regex_pattern($route){
        $preg_quote = self::$preg_quote;
        $regex_search = "%\{$preg_quote\}%";
        $regex_replace = "($preg_quote)";
        $new_route = preg_replace($regex_search,$regex_replace,$route);

        // Make routes with optional parameters tolerant to a missing trailing slash
        $new_route = str_replace("/$regex_replace?","/?$regex_replace?",$new_route);

        // Finally, we create our regex pattern
        $new_route = "/^".str_replace(["/","..."], ["\/","(.*)"], $new_route);
        


        // Make the route tolerant of trailing slashes
        if(substr($new_route,-2) === "\/"){
            $new_route .= "?";
        } else {
            $new_route .= "\/?";
        }
        return "$new_route$/";
    }

    static function get_js_handler($handler,$path,$controller){
        return "";
    }

    static function get_router_context($request_uri){
        // Remove the query string
        $request_uri = str_replace("?".$_SERVER['QUERY_STRING'],"",$request_uri);
        
        // The default context is web
        $context = "web";
        $endpoints = app("api_routes"); // Get our API endpoints

        /** Determine if the admin panel should be an available router context */
        if(!app("Admin_panel_access") && isset($endpoints['admin'])) unset($endpoints['admin']);

        foreach($endpoints as $context_name => $api){
            // Check if our request is in the list of APIs
            if(substr($request_uri,0,strlen($api['prefix'])) === $api['prefix']){
                $context = $context_name;
                break;
            }
        }

        return $context;
    }
}