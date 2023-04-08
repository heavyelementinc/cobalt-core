<?php

use Contact\ContactManager;
use Routes\Route;

Route::get("/?", "CoreAdmin@index", [
    'name' => 'Dashboard',
    'anchor' => [
        'name' => 'Dashboard',
    ],
    'navigation' => ['admin_panel']
]);

if(__APP_SETTINGS__['Posts']['default_enabled']) {
    Route::get("/posts/", "Posts@admin_index",[
        'anchor' => ['name' => __APP_SETTINGS__['Posts']['default_name']],
        'navigation' => ['admin_panel'],
    ]);
    Route::get("/posts/{id}?", "Posts@edit",[
        'handler' => "core/posts.js",
    ]);
}

Route::get("/me/", "UserAccounts@me",
    [
        
    ]
);



/** Control Panel and Settings Editor */

Route::get("/settings/", "CoreAdmin@settings_index", ['anchor' => ['name' => 'Cobalt Settings', 'icon' => 'gear']]);

Route::get("/settings/application/","CoreSettingsPanel@settings_index",[
    'name' => "App Settings",
    'anchor' => [
        'name' => 'App Settings',
        'icon' => "tune-vertical"
    ],
    'navigation' => ['admin_basic_panel'],
    'permission' => "Auth_modify_cobalt_settings"
]);

Route::get("/settings/presentation", "CoreSettingsPanel@presentation",[
    'permission' => 'Auth_modify_cobalt_settings',
    'anchor' => [
        'name' => "Presentation",
        'icon' => 'palette-swatch-variant'
    ],
    'navigation' => ['public_settings_panel'],
    'handler' => 'admin/presentation.js'
]);


/** CONTROL PANEL ITEMS */

if (app('Auth_logins_enabled')) {

    Route::get("/users/", "CoreAdmin@list_all_users", [
        'name' => "Users",
        'handler' => "core/user_panel.js",
        'anchor' => [
            'name' => 'Users',
            'icon' => "account-multiple-plus-outline"
        ],
    'navigation' => ['settings_panel']
    ]);

    Route::get("/create-user", "CoreAdmin@create_user", [
        'handler' => 'core/create_user.js',
        'permission' => 'Auth_allow_creating_users'
    ]);

    Route::get("/users/manage/{user}", "CoreAdmin@individual_user_management_panel", [
        'handler' => 'core/user_manager.js',
        'permission' => "Auth_allow_editing_users"
    ]);
}

if (app("CobaltEvents_enabled")) {
    Route::get("/cobalt-events/edit/{id}?", "EventsController@edit_event", [
        'handler' => 'core/events.js',
        'permission' => "CobaltEvents_crud_events"
    ]);
    Route::get("/cobalt-events/?...?", "EventsController@list_events", [
        'permission' => "CobaltEvents_crud_events",
        'anchor' => [
            'name' => 'Event Manager',
            'href' => '/cobalt-events/',
            'icon' => 'information-outline'
        ],
        'navigation' => ['admin_panel']
    ]);
}

if (app('Plugin_enable_plugin_support')) {
    Route::get("/plugins/", "CoreAdmin@plugin_manager", [
        'permission' => 'Plugins_allow_management',
        'anchor' => [
            'name' => "Plugins",
            'icon' => 'puzzle'
        ],
        'navigation' => ['settings_panel']
    ]);

    Route::get("/plugins/{name}", "CoreAdmin@plugin_individual_manager", ['permission' => 'Plugins_allow_management']);
}

Route::get("/settings/api-keys/", "APIManagement@index",[
    'permission' => 'API_manage_keys',
    'anchor' => [
        'name' => "API Keys",
        'icon' => 'api'
    ],
    'navigation' => ['admin_basic_panel']
]);

Route::get('/settings/api-keys/{name}', "APIManagement@key",[
    'permission' => 'API_manage_keys',
]);


Route::get("/settings/cron", "CoreAdmin@cron_panel",[
    // 'permission' => 'API_manage_keys',
    'anchor' => [
        'name' => "Scheduled Jobs",
        'icon' => 'clock-time-eight-outline'
    ],
    'navigation' => ['settings_panel']
]);

if(__APP_SETTINGS__['PaymentGateways_enabled']) {
    Route::get("/settings/payments", "CoreAdmin@payment_gateways",[
        // 'permission' => 'API_manage_keys',
        'anchor' => [
            'name' => "Payments",
            'icon' => 'credit-card-fast-outline'
        ],
        'navigation' => ['admin_basic_panel']
    ]);
}

if(app("API_contact_form_enabled") && app("Contact_form_interface") === "panel") {
    Route::get("/contact-form/", "ContactForm@index", [
        'anchor' => [
            'name' => "Contact Form",
            'icon' => 'chat-alert-outline',
        ],
        'navigation' => ['admin_panel'],
        'unread' => function () {
            return (new ContactManager())->get_unread_count_for_user(session());
        },
        'handler' => '/core/contact-form.js'
    ]);
    Route::get("/contact-form/{id}", "ContactForm@read");
}


if(app("Customizations_enabled")) {
    Route::get("/customizations/update/{id}", "Customizations@modify_customization",[
        'permission' => 'Customizations_update_parameters'
    ]);

    Route::get("/customizations/edit/{id}", "Customizations@editor", [
        'permission' => 'Customizations_modify'
    ]);

    Route::get("/customizations/{group}?", "Customizations@index", [
        'permission' => 'Customizations_modify',
        'anchor' => [
            'name' => 'Customizations',
            'href' => '/customizations/',
        ],
        'navigation' => ['settings_panel']
    ]);
}
