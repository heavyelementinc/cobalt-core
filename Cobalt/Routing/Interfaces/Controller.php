<?php

namespace Cobalt\Routing\Interfaces;

use Closure;

interface Controller {
    function get(string $path, string|Closure $method):Routeable;
    function head(string $path, string|Closure $method):Routeable;

    function post(string $path, string|Closure $method):Routeable;
    function put(string $path, string|Closure $method):Routeable;
    function delete(string $path, string|Closure $method):Routeable;
}
