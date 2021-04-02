<?php

/** API routes for authorization */
if(app('Auth_logins_enabled')){
    /** Login and logout routes */
    \Routes\Route::post("/login","CoreApi@login");
    \Routes\Route::get("/logout","CoreApi@logout");
    /** User update routes */
    \Routes\Route::put("/user_update/permissions","UserAccounts@update_permissions",['permission' => 'Auth_allow_modifying_user_permissions']);
    \Routes\Route::put("/user_update/basics",     "UserAccounts@update_basics",     ['permission' => 'Auth_allow_editing_users']);
    \Routes\Route::put("/user_update/password",   "UserAccounts@update_password",   ['permission' => 'Auth_allow_editing_users']);
}

if(app('Web_main_content_via_api')){
    \Routes\Route::get("/page","CoreApi@page");
}

if(app('debug')){
    \Routes\Route::get("/hello_world/{something}/{machina}?","HelloWorld@do_it",[ // Hello World test route
        'requires_csrf' => false,
        'requires_cors' => false,
        'permission' => 'Auth_allow_editing_users'
    ]);
}