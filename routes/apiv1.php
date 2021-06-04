<?php

use Routes\Route;

/** API routes for authorization */
if (app('Auth_logins_enabled')) {
    /** Login and logout routes */
    Route::post("/login", "CoreApi@login");
    Route::get("/logout", "CoreApi@logout");
    /** User update routes */
    Route::put("/user_update/permissions", "UserAccounts@update_permissions", ['permission' => 'Auth_allow_modifying_user_permissions']);
    Route::put("/user_update/basics",     "UserAccounts@update_basics",     ['permission' => 'Auth_allow_editing_users']);
    Route::put("/user_update/password",   "UserAccounts@update_password",   ['permission' => 'Auth_allow_editing_users']);
}

if (app('Web_main_content_via_api')) {
    Route::get("/page", "CoreApi@page");
}

if (app('API_contact_form_enabled')) {
    Route::post("/contact", "CoreApi@contact");
}

if (app('debug')) {
    Route::get("/hello_world/{something}/{machina}?", "HelloWorld@do_it", [ // Hello World test route
        'requires_csrf' => false,
        'requires_cors' => false,
        'permission' => 'Auth_allow_editing_users'
    ]);

    Route::post("/debug/validator", "Debug@validate_test_form");
    Route::post("/debug/confirm", "Debug@confirm_test_form");
}
