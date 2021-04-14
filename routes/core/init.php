<?php
$current_route = str_replace(["?$_SERVER[QUERY_STRING]"],"",$_SERVER['REQUEST_URI']);
// Create a username
\Routes\Route::get($current_route,'CoreInit@prompt');
\Routes\Route::post($current_route,'CoreInit@insert');