<?php
use Routes\Route;

/** Debug routes are for testing purposes and should not be enabled in production */
if (app("enable_debug_routes")) {
    Route::get("/new-render/{user_input}?", "DebugRenderer@render", [
        'navigation' => ['debug_settings'],
        'anchor' => ['name' => 'Render Test', 'href' => '/new-render/']
    ]);
    Route::get("/", "Debug@debug_directory");
    Route::get("/settings","Debug@settings",[
        'navigation' => ['debug_settings'],
    'anchor' => ['name' => 'Settings']
    ]);
    Route::get("/renderer", "Debug@debug_renderer", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Renderer"]
    ]);

    Route::get("/notification", "Notifications@debug", [
        'navigation' => ['debug_settings'],
        'anchor' => ['name' => "Notification"]
    ]);

    Route::s_post("/push-test/{recipient}?", "Notifications@pushNotification");

    
    Route::get("/header-tests", "DebugHeaders@page",[
        'navigation' => ['debug_async'],
        'anchor' => ['name' => "Header Test"]
    ]);

    Route::get("/server-control", "Debug@status_modal", [
        'navigation' => ['debug_async'],
        'anchor' => ['name' => "Server Control Headers"]
    ]);

    Route::get("/router", "Debug@debug_router", [
        'navigation' => ['debug_async'],
        'anchor' => ['name' => "Router"]
    ]);

    /** DEBUG COMPONENTS */

    Route::get("/slideshow", "Debug@debug_slideshow", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Slideshow"]
    ]);
    Route::get("/inputs", "Debug@debug_inputs", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Input Test"]
    ]);

    Route::get('/new-form-request', 'Debug@new_form_request',[
        'navigation' => ['debug_components'],
        'anchor' => ['name' => 'New Form Request Test']
    ]);

    
    Route::get("/loading", "Debug@debug_loading", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Loading"]
    ]);
    Route::get("/calendar/{date}?", "Debug@debug_calendar", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Calendar Mode Test", 'href' => '/calendar/']
    ]);
    Route::get("/flex-table", "Debug@flex_table", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Flex Table Test"]
    ]);

    Route::get("/modal", "Debug@modal_test", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Modal"]
    ]);
    Route::get("/action-menu", "Debug@action_menu", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Action Menu"]
    ]);

    Route::get("/colors/{id}?", "Debug@colors", [
        'navigation' => ['debug_components'],
        'anchor' => [
            'href' => '/colors/',
            'name' => "Color Palette Generator"
        ]
    ]);
    Route::get("/next-request", "Debug@next_request_page", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "API Next Request Test"]
    ]);

    Route::get("/file-upload/", "Debug@file_upload_demo", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "File Upload Test"]
    ]);

    Route::get("/draggable", "Debug@drag_drop", [
        'navigation' => ['debug_components'],
        'anchor' => ['name' => "Drag &amp; Drop Test"]
    ]);

    /** DEBUG MISC */



    Route::get("/parallax", "Debug@debug_parallax", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Parallax"]
    ]);



    Route::get("/relative-paths", "Debug@flex_table", [
        'navigation' => ['debug_settings'],
        'anchor' => ['name' => "Relative paths"]
    ]);
    Route::get("/validator", "Debug@form_test", [
        'navigation' => ['debug_async'],
        'anchor' => ['name' => "Validator Test"]
    ]);
    
    Route::get("/async-wizard", "Debug@async_wizard", [
        'navigation' => ['debug_async'],
        'anchor' => ['name' => "Async Wizard"]
    ]);

    
    Route::get("/async-button/", "Debug@async_button", [
        'navigation' => ['debug_async'],
        'anchor' => ['name' => "Async Button Test"]
    ]);

    if (app("debug")) {
        Route::get("/stream/", "Debug@event_stream", [
            'navigation' => ['debug'],
            'anchor' => ['name' => "Server-Sent Events"]
        ]);
        Route::get("/env/", "Debug@environment", [
            'navigation' => ['debug_settings'],
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

    Route::get("/exceptions/", "DebugError@index",[
        'navigation' => ['debug_async'],
        'anchor' => ['name' => 'HTTP Exceptions']
    ]);

    Route::get("/exception/{type}", "DebugError@api_throw_error");
    
    Route::get("/slow-response/{delay}", "Debug@slow_response");
    
    Route::get("/file-upload", "DebugFiles@file_upload_page", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "File upload"]
    ]);
    Route::get("/file-upload/download-test/...", "DebugFiles@download");

    

    Route::get("/twitter", "Debug@twitter", [
        'navigation' => ['debug'],
        'anchor' => ['name' => "Twitter"]
    ]);

    Route::get("/youtube", "Debug@youtube", [
        'navigation' => ['debug', 'debug_demo'],
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

    Route::get("/new-route-group", "Debug@new_route_group", [
        'navigation' => [
            'debug',
            'debug_demo' => [
                'submenu_group' => 'admin_panel'
            ]
        ],
        'anchor' => ['name' => "New Route Group Test"],
    ]);

    Route::get("/router-test/...", "ClientRouterTest@test", [
        'navigation' => [
            'debug'
        ],
        'anchor' => [
            'name' => 'New Router Test',
            'href' => '/router-test/'
        ]
    ]);

    // Route::get("/proto/...", "SchemaDebug@schemaresult",[
    //     'navigation' => [
    //         'debug_prototypes'
    //     ],
    //     'anchor' => ['name' => 'SchemaDebug', 'href' => "/proto/"]
    // ]);
    Route::get("/proto/array", "SchemaDebug@arrayresult",[
        'navigation' => [
            'debug_prototypes'
        ],
        'anchor' => ['name' => 'Array Prototype']
    ]);

    Route::get("/proto/array_each", "SchemaDebug@arrayeach",[
        'navigation' => [
            'debug_prototypes'
        ],
        'anchor' => ['name' => 'Array Each']
    ]);
    
    Route::get("/proto/binary", "SchemaDebug@binaryresult",[
        'navigation' => [
            'debug_prototypes'
        ],
        'anchor' => ['name' => 'Binary Prototype']
    ]);

    Route::get("/proto/bool", "SchemaDebug@boolresult",[
        'navigation' => [
            'debug_prototypes'
        ],
        'anchor' => ['name' => 'Boolean Prototype']
    ]);
    Route::get("/proto/date", "SchemaDebug@dateresult",[
        'navigation' => [
            'debug_prototypes'
        ],
        'anchor' => ['name' => 'Date Prototype']
    ]);
    Route::get("/proto/submap", "SchemaDebug@submapresult",[
        'navigation' => [
            'debug_prototypes'
        ],
        'anchor' => ['name' => 'SubMap Prototype']
    ]);
    Route::get("/proto/uploadimageresult", "SchemaDebug@imageresult",[
        'navigation' => [
            'debug_prototypes'
        ],
        'anchor' => ['name' => 'Images Prototype']
    ]);
}
