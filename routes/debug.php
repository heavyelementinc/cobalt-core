<?php
use Routes\Route;

/** Debug routes are for testing purposes and should not be enabled in production */
if (app("enable_debug_routes")) {
    Route::get("/", "Debug@debug_directory");
    Route::get("/settings","Debug@settings",[
        'navigation' => ['debug'],
    'anchor' => ['name' => 'Settings']
    ]);
    Route::get("/renderer", "Debug@debug_renderer", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Renderer"]
    ]);

    Route::get("/notification", "Notifications@debug", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Notification"]
    ]);

    Route::get("/server-control", "Debug@status_modal", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Server Control Headers"]
    ]);

    Route::get("/router", "Debug@debug_router", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Router"]
    ]);
    Route::get("/slideshow", "Debug@debug_slideshow", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Slideshow"]
    ]);
    Route::get("/inputs", "Debug@debug_inputs", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Input Test"]
    ]);
    Route::get("/parallax", "Debug@debug_parallax", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Parallax"]
    ]);
    Route::get("/loading", "Debug@debug_loading", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Loading"]
    ]);
    Route::get("/calendar/{date}?", "Debug@debug_calendar", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Calendar Mode Test", 'href' => '/calendar/']
    ]);
    Route::get("/flex-table", "Debug@flex_table", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Flex Table Test"]
    ]);
    Route::get("/relative-paths", "Debug@flex_table", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Relative paths"]
    ]);
    Route::get("/validator", "Debug@form_test", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Validator Test"]
    ]);
    Route::get("/modal", "Debug@modal_test", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Modal"]
    ]);
    Route::get("/action-menu", "Debug@action_menu", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Action Menu"]
    ]);
    Route::get("/async-wizard", "Debug@async_wizard", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Async Wizard"]
    ]);
    Route::get("/colors/{id}", "Debug@colors", [
        'navigation' => ['debug'],
        'anchor' => [
            'href' => '/colors/',
            'name' => "Color Palette Generator"
        ]
    ]);
    Route::get("/next-request", "Debug@next_request_page", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "API Next Request Test"]
    ]);

    Route::get("/file-upload/", "Debug@file_upload_demo", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "File Upload Test"]
    ]);
    
    Route::get("/async-button/", "Debug@async_button", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Async Button Test"]
    ]);

    if (app("debug")) {
        Route::get("/stream/", "Debug@event_stream", [
            'navigation' => ['debug'],
            'anchor' => ['name' => "Server-Sent Events"]
        ]);
        Route::get("/env/", "Debug@environment", [
            'navigation' => ['debug'],
            'anchor' => ['name' => "Environment"]
        ]);
        // Route::get("/dump", "Debug@dump", [
        //     'navigation' => ['debug'],
        //     'anchor' => ['name' => "Dump"]
        // ]);
    }
    Route::get("/style-test/", "Debug@style_test", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Style Test"]
    ]);

    
    Route::get("/slow-response/{delay}", "Debug@slow_response");
    
    Route::get("/file-upload", "DebugFiles@file_upload_page", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "File upload"]
    ]);
    Route::get("/file-upload/download-test/...", "DebugFiles@download");

    Route::get("/draggable", "Debug@drag_drop", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Drag &amp; Drop Test"]
    ]);
    Route::get("/twitter", "Debug@twitter", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Twitter"]
    ]);
    Route::get("/youtube", "Debug@youtube", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "YouTube"]
    ]);

    Route::get("/credit-card-test", "Debug@credit_card", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Credit Card Test"]
    ]);

    Route::get("/doc-test", "Debug@doc_test", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Doc Test"]
    ]);

    Route::get("/assoc", "Debug@assoc_test");

    if(is_root()) {
        Route::get("/php", "Debug@phpinfo");
    }
}