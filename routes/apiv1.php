<?php

use Routes\Route;

if(app("UGC_enable_user_generated_content")) {
    Route::post(app("UGC_submit_endpoint"), "UGC@submit");
}

/** API routes for authorization */
if (app('Auth_logins_enabled')) {
    /** Login and logout routes */
    Route::post("/login", "Login@handle_login");
    Route::post("/login/email", "Login@handle_email_login_stage_1");
    Route::get("/logout", "Login@handle_logout");
    /** User update routes */
    Route::s_put("/create-user", "UserAccounts@create_user", ['permission' => 'Auth_allow_creating_users']);

    if(app("Auth_allow_password_reset")) {
        Route::put("/password-reset/request", "Login@api_password_reset_username_endpoint");
        Route::put("/password-reset/{token}", "Login@api_password_reset_username_endpoint");
    }
    
    if(app("Auth_account_creation_enabled")){
        Route::put("/account-creation", "UserAccounts@account_creation");
    }
    
    Route::s_post("/user/me/", "UserAccounts@update_me");
    Route::s_delete("/user/{id}/avatar", "UserAccounts@delete_avatar");
    Route::s_put("/user/{id}/permissions", "UserAccounts@update_permissions", ['permission' => 'Auth_allow_modifying_user_permissions']);
    Route::s_put("/user/{id}/update",     "UserAccounts@update_basics",     ['permission' => 'Auth_allow_editing_users']);
    Route::s_put("/user/{id}/password",   "UserAccounts@update_basics",   ['permission' => 'Auth_allow_editing_users']);
    Route::s_post("/user/{id}/avatar",   "UserAccounts@update_basics",   ['permission' => 'Auth_allow_editing_users']);
    Route::s_put("/user/password",        "UserAccounts@change_my_password", ['permission' => 'self']);
    Route::s_delete("/user/{id}/delete",   "UserAccounts@delete_user",   ['permission' => 'Auth_allow_deleting_users']);

    Route::s_put("/settings/update/", "CoreSettingsPanel@update", [
        'permission' => 'Auth_modify_cobalt_settings'
    ]);

    Route::s_post("/settings/update/", "CoreSettingsPanel@updateLogo", [
        'permission' => 'Auth_modify_cobalt_settings'
    ]);

    Route::s_put("/settings/default/{name}", "CoreSettingsPanel@reset_to_default", [
        'permission' => 'Auth_modify_cobalt_settings'
    ]);
}

if (app('Web_main_content_via_api')) {
    Route::get("/page", "CoreApi@page");
}

if (app('API_contact_form_enabled')) {
    Route::post("/contact", "ContactForm@contact_submit");
    Route::s_put("/contact/read-status/{id}", "ContactForm@read_status");
    Route::s_delete("/contact/delete/{id}", "ContactForm@delete");
}

if (app("CobaltEvents_enabled")) {
    Route::get("/cobalt-events/current", "EventsController@current");
    Route::s_put("/cobalt-events/update/{id}?", "EventsController@update_event", [
        'permission' => 'CobaltEvents_crud_events'
    ]);
    Route::s_delete("/cobalt-events/{id}", "EventsController@delete_event", [
        'permission' => 'CobaltEvents_crud_events'
    ]);
}

if (app("Plugin_enable_plugin_support")) {
    Route::s_post("/plugin/enable/{plugin}", "CoreApi@modify_plugin_state", ['permission' => 'Plugins_allow_management']);
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
    
    
    Route::s_post("/debug/file-upload/single","DebugFiles@simple_file_upload");
    Route::s_post("/debug/file-upload/multi","DebugFiles@multi_file_upload");
    Route::s_post("/debug/file-upload/arbitrary-data","DebugFiles@extra_metadata");
    Route::delete("/debug/file-upload/{id}","DebugFiles@delete");

    Route::put("/debug/control-headers/...", "Debug@control_headers");
    // Route::fs("/debug/file-upload/);

}

if(__APP_SETTINGS__['Posts']['default_enabled']) {
    Route::s_put(   "/posts/{id}/update",             "Posts@update", ['permission' => 'Posts_manage_posts']);
    Route::s_delete("/posts/{id}/delete",             "Posts@deletePost", ['permission' => 'Posts_manage_posts']);
    Route::s_post(  "/posts/{id}/upload",             "Posts@upload", ['permission' => 'Posts_manage_posts']);
    Route::s_delete("/posts/attachment/{id}",         "Posts@delete", ['permission' => 'Posts_manage_posts']);
    Route::s_put(   "/posts/attachment/{id}/default", "Posts@defaultImage", ['permission' => 'Posts_manage_posts']);
    Route::s_put(   "/posts/attachment/{id}/sort",    "Posts@updateSortOrder", ['permission' => 'Posts_manage_posts']);
}

if(__APP_SETTINGS__['PaymentGateways_enabled']) {
    Route::s_put("/settings/payment-gateways/{id}", "CoreApi@update_gateway_data", ['permission' => '']);
}


Route::s_put("/api/key/{service}",  "APIManagement@update", ['permission' => 'API_manage_keys']);
Route::s_post("/api/key/{service}", "APIManagement@parse",  ['permission' => 'API_manage_keys']);
