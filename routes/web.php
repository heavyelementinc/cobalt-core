<?php

use Routes\Route;

Route::get("/", "Pages@index", [
    'anchor' => ['name' => 'Home'],
    'navigation' => ['main_navigation']
]);

/** Debug routes are for testing purposes and should not be enabled in production */
if (app("enable_debug_routes")) {
    Route::get("/debug", "Debug@debug_directory");
    Route::get("/debug/renderer", "Debug@debug_renderer", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Renderer"]
    ]);
    Route::get("/debug/router", "Debug@debug_router", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Router"]
    ]);
    Route::get("/debug/slideshow", "Debug@debug_slideshow", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Slideshow"]
    ]);
    Route::get("/debug/inputs", "Debug@debug_inputs", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Input Test"]
    ]);
    Route::get("/debug/parallax", "Debug@debug_parallax", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Parallax"]
    ]);
    Route::get("/debug/loading", "Debug@debug_loading", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Loading"]
    ]);
    Route::get("/debug/calendar/{date}?", "Debug@debug_calendar", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Calendar Mode Test", 'href' => '/debug/calendar/']
    ]);
    Route::get("/debug/flex-table", "Debug@flex_table", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Flex Table Test"]
    ]);
    Route::get("/debug/relative-paths", "Debug@flex_table", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Relative paths"]
    ]);
    Route::get("/debug/validator", "Debug@form_test", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Validator Test"]
    ]);
    Route::get("/debug/modal", "Debug@modal_test", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Modal"]
    ]);
    Route::get("/debug/action-menu", "Debug@action_menu", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Action Menu"]
    ]);
    Route::get("/debug/slow-response/{delay}", "Debug@slow_response");
}

/** If authentications are enabled, these routes should be added to the table */
if (app("Auth_logins_enabled")) {
    /** Basic login page */
    Route::get(app("Auth_login_page"), "UserAccounts@login");

    // /** Admin panel (TODO: Implement admin panel) */
    // Route::get(app("Admin_panel_prefix"), "CoreController@admin_panel",['permission' => 'Admin_panel_access']);
}
