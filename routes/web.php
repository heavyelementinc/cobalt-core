<?php

use Routes\Route;

if(app("UGC_enable_user_generated_content")) {
    Route::get(trim_trailing_slash(app("UGC_retrieval_endpoint")) . "/{file_id}", "UGC@retrieve");
}

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
    Route::get("/debug/async-wizard", "Debug@async_wizard", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Async Wizard"]
    ]);
    Route::get("/debug/colors/{id}", "Debug@colors", [
        'navigation' => ['debug'],
        'anchor' => [
            'href' => '/debug/colors/',
            'name' => "Color Palette Generator"
        ]
    ]);
    Route::get("/debug/next-request", "Debug@next_request_page", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "API Next Request Test"]
    ]);

    Route::get("/debug/file-upload/", "Debug@file_upload_demo", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "File Upload Test"]
    ]);
    
    Route::get("/debug/async-button/", "Debug@async_button", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Async Button Test"]
    ]);

    if (app("debug")) {
        Route::get("/debug/stream/", "Debug@event_stream", [
            'navigation' => ['debug'],
            'anchor' => ['name' => "Server-Sent Events"]
        ]);
        Route::get("/debug/env/", "Debug@environment", [
            'navigation' => ['debug'],
            'anchor' => ['name' => "Environment"]
        ]);
        // Route::get("/debug/dump", "Debug@dump", [
        //     'navigation' => ['debug'],
        //     'anchor' => ['name' => "Dump"]
        // ]);
    }
    Route::get("/debug/style-test/", "Debug@style_test", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Style Test"]
    ]);

    
    Route::get("/debug/slow-response/{delay}", "Debug@slow_response");
    
    Route::get("/debug/file-upload", "DebugFiles@file_upload_page", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "File upload"]
    ]);
    Route::get("/debug/file-upload/download-test/...", "DebugFiles@download");
}

/** If authentications are enabled, these routes should be added to the table */
if (app("Auth_logins_enabled")) {
    /** Basic login page */
    Route::get(app("Auth_login_page"), "Login@login");
    // Route::get("/preferences/password-reset-required/", "UserAccounts@change_my_password");
    // /** Admin panel (TODO: Implement admin panel) */
    // Route::get(app("Admin_panel_prefix"), "CoreController@admin_panel",['permission' => 'Admin_panel_access']);

    Route::get("/user/menu", "UserAccounts@get_user_menu");
    Route::get("/admin", "CoreController@admin_redirect");
}

if (app("Auth_account_creation_enabled")) {
    Route::get(app("Auth_onboading_url"), "UserAccounts@onboarding");
}