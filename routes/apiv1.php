<?php

use Routes\Route;

if(app("UGC_enable_user_generated_content")) {
    Route::post(app("UGC_submit_endpoint"), "UGC@submit");
}

/** API routes for authorization */
if (app('Auth_logins_enabled')) {
    /** Login and logout routes */
    Route::post("/login", "CoreApi@login");
    Route::get("/logout", "CoreApi@logout");
    /** User update routes */
    Route::put("/create-user", "UserAccounts@create_user", ['permission' => 'Auth_allow_creating_users']);
    
    if(app("Auth_account_creation_enabled")){
        Route::put("/account-creation", "UserAccounts@account_creation");
    }
    
    Route::put("/user/{id}/permissions", "UserAccounts@update_permissions", ['permission' => 'Auth_allow_modifying_user_permissions']);
    Route::put("/user/{id}/update",     "UserAccounts@update_basics",     ['permission' => 'Auth_allow_editing_users']);
    Route::put("/user/{id}/password",   "UserAccounts@update_basics",   ['permission' => 'Auth_allow_editing_users']);
    Route::put("/user/password",        "UserAccounts@change_my_password", ['permission' => 'self']);
    Route::delete("/user/{id}/delete",   "UserAccounts@delete_user",   ['permission' => 'Auth_allow_deleting_users']);
}

if (app('Web_main_content_via_api')) {
    Route::get("/page", "CoreApi@page");
}

if (app('API_contact_form_enabled')) {
    Route::post("/contact", "CoreApi@contact");
}

if (app("CobaltEvents_enabled")) {
    Route::get("/cobalt-events/current", "EventsController@current");
    Route::put("/cobalt-events/update/{id}?", "EventsController@update_event", [
        'permission' => 'CobaltEvents_crud_events'
    ]);
}

if (app("Plugin_enable_plugin_support")) {
    Route::post("/plugin/enable/{plugin}", "CoreApi@modify_plugin_state", ['permission' => 'Plugins_allow_management']);
}

if (app('debug')) {
    Route::get("/hello_world/{something}/{machina}?", "HelloWorld@do_it", [ // Hello World test route
        'requires_csrf' => false,
        'requires_cors' => false,
        'permission' => 'Auth_allow_editing_users'
    ]);

    Route::post("/debug/upload/", "Debug@upload_test");
    Route::post("/debug/upload-and-watch/", "Debug@image_test");
    // Route::post("/debug/upload-and-watch/", "Debug@s3_test");

    Route::post("/debug/validator", "Debug@validate_test_form");
    Route::post("/debug/confirm", "Debug@confirm_test_form");
    Route::get("/debug/slow-response", "Debug@slow_response");
    Route::get("/debug/slow-error", "Debug@slow_error");
    
    Route::post("/debug/next-request", "Debug@next_request_post");
    Route::put("/debug/next-request", "Debug@next_request_put");
    
    
    Route::post("/debug/file-upload/single","DebugFiles@simple_file_upload");
    Route::post("/debug/file-upload/multi","DebugFiles@multi_file_upload");
    Route::post("/debug/file-upload/arbitrary-data","DebugFiles@extra_metadata");
    Route::delete("/debug/file-upload/{id}","DebugFiles@delete");


    // Route::fs("/debug/file-upload/);

}

