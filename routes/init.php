<?php

use Routes\Route;

$current_route = str_replace(["?$_SERVER[QUERY_STRING]"], "", $_SERVER['REQUEST_URI']);
// Create a username
Route::get($current_route, 'CoreInit@prompt');
Route::post($current_route, 'CoreInit@insert');
