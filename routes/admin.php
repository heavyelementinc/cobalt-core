<?php

use Routes\Route;

Route::get("/?", "CoreAdmin@index", [
    'name' => 'Dashboard',
    'anchor' => [
        'name' => 'Dashboard',
    ],
    'navigation' => ['admin_panel']
]);

if (app('Auth_logins_enabled')) {
    /** User management interface */
    Route::get("/create-user", "CoreAdmin@create_user", [
        'handler' => 'core/create_user.js',
        'permission' => 'Auth_allow_creating_users'
    ]);
    Route::get("/users/", "CoreAdmin@manage_users", [
        'name' => "Users",
        'handler' => "core/user_panel.js",
        'anchor' => [
            'name' => 'Users'
        ],
        'navigation' => ['admin_panel']
    ]);
    Route::get(app("Auth_user_manager_individual_page") . "/{user}", "CoreAdmin@user_manager", [
        'handler' => 'core/user_manager.js',
        'permission' => "Auth_allow_editing_users"
    ]);
}
