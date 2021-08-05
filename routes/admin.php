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
    Route::get("/users/", "CoreAdmin@list_all_users", [
        'name' => "Users",
        'handler' => "core/user_panel.js",
        'anchor' => [
            'name' => 'Users'
        ],
        'navigation' => ['admin_panel']
    ]);
    Route::get(app("Auth_user_manager_individual_page") . "/{user}", "CoreAdmin@individual_user_management_panel", [
        'handler' => 'core/user_manager.js',
        'permission' => "Auth_allow_editing_users"
    ]);
}

if (app("CobaltEvents_enabled")) {
    Route::get("/cobalt-events/edit/{id}?", "EventsController@edit_event", ['permission' => "CobaltEvents_crud_events"]);
    Route::get("/cobalt-events/?...?", "EventsController@list_events", [
        'permission' => "CobaltEvents_crud_events",
        'anchor' => [
            'name' => 'Event Manager',
            'href' => '/cobalt-events/'
        ],
        'navigation' => ['admin_panel']
    ]);
}

if (app('Plugin_enable_plugin_support')) {
    Route::get("/plugins/", "CoreAdmin@plugin_manager", [
        'permission' => 'Plugins_allow_management',
        'anchor' => [
            'name' => "Plugins",
        ],
        'navigation' => ['admin_panel']
    ]);

    Route::get("/plugins/{name}", "CoreAdmin@plugin_individual_manager", ['permission' => 'Plugins_allow_management']);
}
