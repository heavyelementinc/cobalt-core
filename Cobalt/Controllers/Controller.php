<?php

namespace Cobalt\Controllers;

use Routes\Options;
use Routes\Route;

class Controller {
    static function route_details(array $default_values, array $details, string $callable) {
        $callable_results = static::$callable($details);
        return array_merge($default_values, $callable_results, $details);
    }

    static function generate_prefix($supplied):string {
        if($supplied) {
            if($supplied[0] !== "/") $supplied = "/$supplied";
            return $supplied;
        }
        $supplied = (new \ReflectionClass(static::className()))->getShortName();
        $prefix = preg_replace('/([A-Z])/', '-$1',$supplied);
        if($prefix[0] == "-") $prefix = substr($prefix, 1);
        return "/" . strtolower($prefix);
    }

    static function permissions(?array $permissions) {
        $merged = $permissions ?? [];
        return $merged;
    }

    static function className() {
        return static::class;
    }
    
    static function route(Options $route):Options {
        $route->set_controller(static::className()."@".$route->get_controller());
        return $route;
    }

    static function get(Options $route) {
        static::route($route, 'get');
        Route::get($route);
    }

    static function s_get(Options $route){
        static::route($route, 's_get');
        Route::s_get($route);
    }

    static function post(Options $route) {
        static::route($route, 'post');
        Route::post($route);
    }

    static function s_post(Options $route){
        static::route($route, 's_post');
        Route::s_post($route);
    }

    static function delete(Options $route) {
        static::route($route, 'delete');
        Route::delete($route);
    }

    static function s_delete(Options $route){
        static::route($route, 's_delete');
        Route::s_delete($route);
    }

    static function put(Options $route) {
        static::route($route, 'put');
        Route::put($route);
    }

    static function s_put(Options $route){
        static::route($route, 's_put');
        Route::s_put($route);
    }

    static function get_route_href(string $methodName, array $args = [], array $context = [], bool $throw = true) {
        return route(static::className()."@$methodName", $args, $context, $throw);
    }

    static function get_route_replace(string $methodName, array $args, array $data = []) {
        return route_replacement(static::className()."@$methodName", $args, $data);
    }

    static function generate_friendly_name(?string $supplied = null):string {
        if($supplied) return $supplied;
        $supplied = (new \ReflectionClass(static::className()))->getShortName();
        $prefix = preg_replace('/([A-Z])/', ' $1',$supplied);
        if($prefix[0] == "-") $prefix = substr($prefix, 1);
        return trim($prefix);
    }
}