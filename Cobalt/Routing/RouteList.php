<?php

namespace Cobalt\Routing;

use ArrayAccess;
use Cobalt\Routing\Enums\HttpMethods;
use Iterator;

/**
 * @implements Iterator<int,Route>
 * @package Cobalt\Routing
 */
class RouteList implements ArrayAccess, Iterator {

    private int $index = 0;
    /**
     * @var Route[]
     */
    private array $canonicalList = [];
    public private(set) array $typedList = [
        'get'    => [],
        'post'   => [],
        'put'    => [],
        'delete' => [],
    ];

    function __construct() {
        
    }

    public function addRoute(Route $route):Route {
        $index = count($this->typedList[$route->method->value]);
        $this->typedList[$route->method->value][] = $route;
        $route->setNaturalOrder($index);
        $this->canonicalList[] = $route;
        return $route;
    }

    public function getListByType(HttpMethods $type):array {
        if($type === HttpMethods::HEAD) return $this->canonicalList;
        return $this->typedList[$type->value];
    }

    public function current(): mixed {
        return $this->canonicalList[$this->index];
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        return $this->index;
    }

    public function valid(): bool {
        return key_exists($this->index, $this->canonicalList);
    }

    public function rewind(): void {
        $this->index = 0;
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset,$this->canonicalList);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->canonicalList[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->canonicalList[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->canonicalList[$offset]);
    }

}
