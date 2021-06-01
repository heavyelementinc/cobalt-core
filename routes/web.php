<?php

use Routes\Route;

Route::get("/", "Pages@index", [
    'anchor' => [
        'name' => 'Home'
    ],
    'navigation' => [
        'main_navigation'
    ]
]);

/** Debug routes are for testing purposes and should not be enabled in production */
if (app("enable_debug_routes")) {
    Route::get("/debug/renderer", "Debug@debug_renderer");
    Route::get("/debug/router", "Debug@debug_router");
    Route::get("/debug/slideshow", "Debug@debug_slideshow");
    Route::get("/debug/inputs", "Debug@debug_inputs");
    Route::get("/debug/parallax", "Debug@debug_parallax");
    Route::get("/debug/loading", "Debug@debug_loading");
    Route::get("/debug/calendar/{date}?", "Debug@debug_calendar");
    Route::get("/debug/flex-table", "Debug@flex_table");
    Route::get("/debug/relative-paths", "Debug@flex_table");
    Route::get("/debug/validator", "Debug@form_test");
    Route::get("/debug/modal", "Debug@modal_test");
    Route::get("/debug/slow-response/{{delay}}", "Debug@slow_response");
}

/** If authentications are enabled, these routes should be added to the table */
if (app("Auth_logins_enabled")) {
    /** Basic login page */
    Route::get(app("Auth_login_page"), "CoreController@login", ['handler' => 'core/login.js']);
    // /** Admin panel (TODO: Implement admin panel) */
    // Route::get(app("Admin_panel_prefix"), "CoreController@admin_panel",['permission' => 'Admin_panel_access']);
}
