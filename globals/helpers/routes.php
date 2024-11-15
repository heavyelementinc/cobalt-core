<?php


// function get_route_data(string $class, string $method, ?string $routeMethod = "get", string $context = null) {
    // global $ROUTER;
//     if($context === null) $context = "web";
//     $controllerAlias = "$class@$method";
//     $router = $ROUTER;
//     if(key_exists($controllerAlias, $GLOBALS['ROUTE_LOOKUP_CACHE'])) return route_replacement($GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias], $args, []);
//     // if($context !== $router->route_context) {
//     //     if(isset($GLOBALS['api_router'])) $router = $GLOBALS['api_router'];
//     //     if($context !== $router->route_context) throw new Error("Could not establish proper context");
//     // }
//     // $routes = $router->routes[$context][$routeMethod];
//     $route = null;
//     foreach($router->routes as $routes) {
//         foreach($routes[$routeMethod] as $r => $data) {
//             if($data['controller'] !== $controllerAlias) continue;
//             $GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias] = $data['real_path'];
//             return $data;
//         }
//     }
// }

use Cobalt\Pages\PageManager;
use Routes\Router;

/**
 * Limitations: this will only return the first route that uses the specified controller
 * @param string $class
 * @param string $method
 * @param array $args 
 * @param mixed $args 
 * 
 * @return string 
 */
function get_path_from_route(string $class, string $method, array $args = [], ?string $routeMethod = "get", string $context = null) {
    global $ROUTER;
    global $ROUTE_LOOKUP_CACHE;
    if($context === null) $context = "web";
    $controllerAlias = "$class@$method";
    if(!$ROUTER) {
        $ROUTER = new Router("web", "GET");
    } 
    $ROUTER->init_route_table();
    $ROUTER->get_routes();
    if(key_exists($controllerAlias, $ROUTE_LOOKUP_CACHE) && $ROUTE_LOOKUP_CACHE[$controllerAlias] !== null) {
        return route_replacement($ROUTE_LOOKUP_CACHE[$controllerAlias], $args, []);
    }
    // if($context !== $router->route_context) {
    //     if(isset($GLOBALS['api_router'])) $router = $GLOBALS['api_router'];
    //     if($context !== $router->route_context) throw new Error("Could not establish proper context");
    // }
    // $routes = $router->routes[$context][$routeMethod];
    $route = null;
    foreach($ROUTER->routes as $routes) {
        foreach($routes[$routeMethod] as $r => $data) {
            if($data['controller'] !== $controllerAlias) continue;
            $GLOBALS['ROUTE_LOOKUP_CACHE'][$controllerAlias] = $data['real_path'];
            return route_replacement($data['real_path'], $args, $data);
        }
    }

    $ROUTE_LOOKUP_CACHE[$controllerAlias] = $route;
    return $route;
}

function route_replacement($path, $args, $data = []) {
    $rt = $path;
    $regex = "/(\{{1}[a-zA-Z0-9]*\}{1}\??)|\.{3}/";
    
    $replacement = [];
    preg_match_all($regex,$rt,$replacement);

    $mutant = $rt;
    // if(gettype($replacement[0]) !== "array") $replacement[0] = [$replacement[0]];
    foreach($replacement[0] as $i => $replace) {
        $mutant = str_replace($replace, $args[$i] ?? $args[0] ?? "", $mutant);
    }

    return preg_replace("/\/{2,}/","/", $mutant);
}

/**
 * This will only return the first route that uses $directiveName
 * @param string $directiveName the "Controller@method" direvitve specified in your router table
 * @param array $args Any arguments used here will get filled in as values for {variables} in route names from left to right
 * @param array $context The context to search ("web", "admin", "apiv1", etc.)
 * @return string 
 * @throws Exception 
 */
function route(string $directiveName, array $args = [], array $context = []):string {
    $routeMethod = $context['method'] ?? "get";
    $ctx = $context['context'] ?? "web";
    $split = explode("@", $directiveName);
    
    $route = get_path_from_route($split[0], $split[1], $args, $routeMethod, $ctx);
    if(!$route) {
        $flag = get_crudable_flag($split[0]);
        if($flag === null) throw new Exception("Could not find route based on directive name.");
        if($flag !== CRUDABLE_CONFIG_ADMIN + CRUDABLE_CONFIG_APIV1) throw new Exception("Crudable has not been configured");
        throw new Exception("Could not find route based on directive name.");
    }
    return $route;
}

function validate_route($directiveName, $context) {
    global $ROUTER;
    $routeMethod = $context['method'] ?? "get";
    $ctx = $context['context'] ?? "web";
    
    $routes = $ROUTER->routes[$ctx][$routeMethod];

    foreach($routes as $r => $data) {
        if($data['controller'] !== $directiveName) continue;
        return true;
    }

    return false;
}

// TODO: Fix this
/** Create a directory listing from existing web GET routes
 * 
 * with_icon, prefix, classes, id, (array) ulPrefix, (array) ulSuffix, (bool) excludeWrapper
 * 
 * @param string $directory_group the name of the key
 */
function get_route_group_old($directory_group, $misc = []) {
    global $ROUTER;
    $misc = array_merge(['with_icon' => false, 'ulPrefix' => "", 'excludeWrapper' => false, 'classes' => "", 'id' => ""], $misc);
    if ($misc['with_icon']) $misc['classes'] .= " directory--icon-group";
    if ($misc['id']) $misc['id'] = "id='$misc[id]' ";
    if ($misc['classes']) $misc['classes'] = " $misc[classes]";
    
    // Check if we have prefixes or suffixes specified
    
    $ul = "<ul $misc[id]" . "class='directory--group$misc[classes]'>";
    if($misc['excludeWrapper'] === true) $ul = "";
    $current_route = $ROUTER->current_route;
    $list = $ROUTER->routes;

    // handleAuxiliaryRoutes($list, $misc, $directory_group);

    $group_to_process = [];

    foreach($list as $context => $methods) {
        foreach($methods as $method => $routes) {
            $nat_order = -1;
            foreach ($routes as $r => $route) {
                $groups = $route['navigation'] ?? false;
                if (!$groups) continue;
                // Now we check if the directory group is in $groups or the key exists
                // If both are FALSE, then we skip list assembly.
                if (!in_array($directory_group, $groups) && !key_exists($directory_group, $groups)) continue;
                if ($route['permission'] && !has_permission($route['permission'], null, null, false)) continue;
                $nat_order++;
                $group_to_process[] = [...$route, ...['r' => $r, 'context' => $context, 'current_nav_group' => $directory_group, 'nat_order' => $nat_order]];
            }
        }
    }

    uasort($group_to_process, function ($a, $b) {
        $order_a = $a['anchor']['order'] ?? $a['navigation'][$a['current_nav_group']]['order'] ?? $a['nat_order'];
        $order_b = $b['anchor']['order'] ?? $b['navigation'][$b['current_nav_group']]['order'] ?? $b['nat_order'];
        return $order_a - $order_b;
    });

    foreach($group_to_process as $key => $route) {
        $info = $groups[$directory_group] ?? $route['anchor'] ?? [];
        if(key_exists('unread',$route)) $info['unread'] = $route['unread'];
        if(!isset($info['name']) && isset($route['anchor'])) $info = array_merge($route['anchor'], $info);
        if ($route['r'] === $current_route) $info['attributes'] = 'class="current--route"';
        $ul .= build_directory_item($info, $misc['with_icon'], $route['context']);
    }

    $wrapper = ($misc['excludeWrapper']) ? "" : "</ul>";
    return $ul . $wrapper;
}

function get_route_group($directory_group, $misc = []) {
    global $ROUTER;
    $misc = array_merge(['with_icon' => false, 'ulPrefix' => "", 'excludeWrapper' => false, 'classes' => "", 'id' => ""], $misc);
    $rtGrp = new \Routes\RouteGroup($directory_group, $ROUTER->current_route ?? "",$misc['with_icon']);
    $rtGrp->setID($misc['id']);
    $rtGrp->setClassesFromString($misc['classes']);
    $rtGrp->setExcludeWrappers($misc['excludeWrapper']);
    $landingPages = new PageManager();
    $pageData = [];
    foreach($landingPages->find($landingPages->public_query(['include_in_route_group' => true, 'route_group' => $directory_group])) as $page) {
        $pageData[] = [
            'href' => $page->url_slug->get_path(),
            'label' => $page->route_link_label->getValue() ?? $page->title->getValue(),
            'order' => $page->route_order->getValue(),
        ];
    }
    if(!empty($pageData)) $rtGrp->setExternalLinks($pageData);
    return $rtGrp->render();
}

// TODO: Fix this
function handleAuxiliaryRoutes(&$list, $misc, $group):void {
    $prefix = ($misc['ulPrefix']) ? $misc['ulPrefix'] : [];
    $suffix = ($misc['ulSuffix']) ? $misc['ulSuffix'] : [];
    // If the prefixes or suffixes are strings, make them arrays
    if(gettype($prefix) === "string") $prefix = [$prefix];
    if(gettype($suffix) === "string") $suffix = [$suffix];
    $mutantPrefix = [];
    foreach($prefix as $pfx) {
        $mutantPrefix += auxRouteHandler($pfx, $group);
    }

    $mutantSuffix = [];

    foreach($suffix as $sfx) {
        array_push($mutantSuffix, [$sfx => auxRouteHandler($sfx, $group)]);
    }

    foreach($list as $element) {
        array_unshift($element['get'], ...$mutantPrefix);
        array_push($element['get'], ...$mutantSuffix);
    }
}

// TODO: Fix this
function auxRouteHandler($route, $group) {
    if(is_string($route)) {
        if(strpos($route,"@") !== false) {
            $rt = route($route);
            $rt['groups'] === [$group];
            return $rt;
        } 
        return [
            "/" . preg_quote($route) . "/" => [
                'original_path' => $route,
                'controller' => "",
                'anchor' => [
                    'label' => $route,
                    'href' => $route
                ],
                'groups' => [$group]
            ]
        ];
    } else if (is_array($route)) return route(...array_values($route));
    throw new Exception("Provided auxiliary is not a valid auxiliary route type");
}

function build_directory_item($item, $icon = false, $context = "") {
    $prefix = "";
    $icon = "";
    if ($icon) $icon = "<i name='$item[icon]'></i>";
    $attributes = $item["attributes"] ?? '';
    if ($context !== "web") {
        $prefix = app('context_prefixes')[$context]['prefix'];
        if($prefix[strlen($prefix) - 1] == "/") $prefix = substr($prefix, 0, -1);
    }
    $submenu = "";
    if (isset($item['submenu_group'])) $submenu = get_route_group($item['submenu_group'], ['classes' => 'directory--submenu', 'icon' => $icon, 'prefix' => $prefix]);
    if(strpos($submenu,'current--route')) {
        $current_route_classes = 'current--route current--route--parent';
        if(isset($item['attributes'])) {
            $items['attributes'] = substr($item['attributes'],-1) . "$current_route_classes\"";
        }
    }
    $unread = "";
    if (isset($item['unread']) && $item['unread'] instanceof \Closure) $unread_count = $item['unread']($item);
    if($unread_count) $unread = "<span class='unread'>$unread_count</span>";

    return "<li><a href='$prefix$item[href]' $attributes>$icon" . "$item[name]$unread</a>$submenu</li>";
}

function get_schema_group_names(string $group_name, array $schema) {
    $elements = [];
    foreach ($schema as $field => $value) {
        if (isset($value['groups']) && in_array($group_name, $value['groups'])) $elements += [$field => $value];
    }
    return $elements;
}

function get_schema_group_elements($group_name, $schema) {
}

function schema_group_element($tag, $attributes, $label = "") {
    $closures = [
        'input' => "",
        'default' => "</$tag>"
    ];
    $attrs = "";
    foreach ($attributes as $key => $value) {
        if (is_callable(($value))) $value = $value($key, $attributes, $label);
        $attrs = " $key=\"" . htmlspecialchars($value) . "\"";
    }
    return "<$tag$attributes>";
}

