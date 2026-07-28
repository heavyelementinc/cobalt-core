<?php

namespace Cobalt\Routing\Interfaces;

use Closure;
use Iterator;
use Routes\Options;

/**
 * @package Cobalt\Routing\Interfaces
 */
interface Routeable {

    static function get(Controller $controller, string $path, string|Closure $method):Routeable;

    static function post(Controller $controller, string $path, string|Closure $method):Routeable;

    static function delete(Controller $controller, string $path, string|Closure $method):Routeable;

    static function put(Controller $controller, string $path, string|Closure $method):Routeable;

    static function head(Controller $controller, string $path, string|Closure $method):Routeable;

    function toArray():array;

    static function fromArray(array $array): Routeable
}
