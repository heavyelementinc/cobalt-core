<?php

use Routes\Route;

if (app('debug')) {
    Route::get("/hello-world/?", "EventStream@start_streaming", [ // Hello World test route
        'requires_csrf' => false,
        'requires_cors' => false
    ]);
}

Route::get("/watch/", "EventStream@watch");
