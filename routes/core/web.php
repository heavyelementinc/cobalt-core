<?php
Routes\Route::get("/","Pages@index");

/** Debug routes are for testing purposes and should not be enabled in production */
if(app("enable_debug_routes")) {
    Routes\Route::get("/debug/renderer","Debug@debug_renderer");
    Routes\Route::get("/debug/router","Debug@debug_router");
    Routes\Route::get("/debug/slideshow","Debug@debug_slideshow");
    Routes\Route::get("/debug/inputs","Debug@debug_inputs");
    Routes\Route::get("/debug/parallax","Debug@debug_parallax");
    Routes\Route::get("/debug/loading","Debug@debug_loading");
    Routes\Route::get("/debug/calendar","Debug@debug_calendar");
}

/** If authentications are enabled, these routes should be added to the table */
if(app("Auth_logins_enabled")) {
    /** Basic login page */
    Routes\Route::get(app("Auth_login_page"),"CoreController@login",['handler' => 'core/login.js']);
    // /** Admin panel (TODO: Implement admin panel) */
    // Routes\Route::get(app("Admin_panel_prefix"), "CoreController@admin_panel",['permission' => 'Admin_panel_access']);
}