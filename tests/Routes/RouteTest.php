<?php

namespace Tests\Routes;

use PHPUnit\Framework\TestCase;
use Routes\Route;

class RouteTest extends TestCase {
    public function route_string_based_option(): void {
        // given we have a router object
        Route::get("/route",);
        // when we call register a method

        // then we assert route was registered
    }
}