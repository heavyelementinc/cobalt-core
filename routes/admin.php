<?php

use Contact\ContactManager;
use Routes\Route;

Route::get("/", "CoreAdmin@index", [
    'name' => 'Dashboard',
    'anchor' => [
        'name' => 'Dashboard',
    ],
    'navigation' => ['admin_panel']
]);

if(__APP_SETTINGS__['Posts']['default_enabled']) {
    Posts::admin();
    // Route::get("/posts/", "Posts@index",[
    //     'anchor' => ['name' => __APP_SETTINGS__['Posts']['default_name']],
    //     'navigation' => ['admin_panel'],
    // ]);
    // Route::get("/posts/{id}?", "Posts@edit",[
    //     // 'handler' => "core/posts.js",
    // ]);
}

LandingPages::admin();

Route::get("/me/", "UserAccounts@me",
    [
        
    ]
);



/** ========================================================
 *  ========================================================
 *  ================== CONTROL PANEL =======================
 *  ========================================================
 *  ========================================================
 * 
 */

    /** Control Panel and Settings Editor */

    Route::get("/settings/", "CoreAdmin@settings_index", [
        'anchor' => ['name' => 'Cobalt Settings', 'icon' => 'gear']
    ]);

/** 
*  ========================================================
*  ================ PRESENTATION SETTINGS =================
*  ========================================================
*/
    Route::get("/settings/presentation", "CoreSettingsPanel@presentation",[
        'permission' => 'Auth_modify_cobalt_settings',
        'anchor' => [
            'name' => "Presentation",
            'icon' => 'palette-swatch-outline',
            'icon_color' => 'linear-gradient(to bottom, #DA627D, #FF495C 80%)'
        ],
        'navigation' => ['presentation_settings'],
        'handler' => 'admin/presentation.js'
    ]);

    if(app("Customizations_enabled")) {
        Route::get("/customizations/list", "Customizations@list", [
            'permission' => 'Customizations_update_parameters',
        ]);
        Route::get("/customizations/update/{id}", "Customizations@modify_customization",[
            'permission' => 'Customizations_update_parameters'
        ]);

        Route::get("/customizations/edit/{id}", "Customizations@editor", [
            'permission' => 'Customizations_modify'
        ]);

        Route::get("/customizations/{group}?", "Customizations@index", [
            'permission' => 'Customizations_modify',
            'anchor' => [
                'icon' => 'application-edit-outline',
                'icon_color' => "linear-gradient(45deg, #09009f, #00ff95 80%)",
                'name' => 'Customizations',
                'href' => '/customizations/',
            ],
            'navigation' => ['presentation_settings']
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
                'icon' => 'information-outline',
                'icon_color' => 'linear-gradient(0.5turn, #14BDEB, #9d3cf6 80%)',
            ],
            'navigation' => ['admin_panel', 'presentation_settings']
        ]);
    }
    
/** 
*  ========================================================
*  ================ APPLICATION SETTINGS ==================
*  ========================================================
*/

    Route::get("/settings/application/","CoreSettingsPanel@settings_index",[
        'name' => "App Settings",
        'anchor' => [
            'name' => 'App Settings',
            'icon' => "tune-vertical",
            'icon_color' => "#5CDEFF",
        ],
        'navigation' => ['application_settings'],
        'permission' => "Auth_modify_cobalt_settings"
    ]);

    if (app('Auth_logins_enabled')) {

        Route::get("/users/", "CoreAdmin@list_all_users", [
            'name' => "Users",
            'handler' => "core/user_panel.js",
            'anchor' => [
                'name' => 'Users',
                'icon' => "account-group-outline",
                'icon_color' => "#FF5964"
            ],
        'navigation' => ['application_settings']
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

    // Route::get("/settings/fs-manager", "CoreSettingsPanel@fileManager",[
    //     'permission' => 'Customizations_modify',
    //     'anchor' => [
    //         'name' => "FS Manager",
    //         'icon' => 'palette-swatch-outline',
    //         'icon_color' => 'linear-gradient(to bottom, #DA627D, #FF495C 80%)'
    //     ],
    //     'navigation' => ['application_settings'],
    //     'handler' => 'admin/fs-manager.js'
    // ]);
    // Route::get("/settings/fs-manager", "CrudableFiles@__index");
    CrudableFiles::admin();
/** 
*  ========================================================
*  ================= ADVANCED SETTINGS ====================
*  ========================================================
*/

    Route::get("/extensions/", "ExtensionsController@index", [
        'permission' => 'Extensions_allow_management',
        'anchor' => [
            'name' => "Extensions",
            'icon' => 'puzzle-outline',
            'icon_color' => "linear-gradient(to top, #004BA8, #65AFFF)"
        ],
        'navigation' => ['advanced_settings']
    ]);

    Route::get("/extensions/{uuid}", "ExtensionsController@extension", [
        'permission' => 'Extensions_allow_management',
    ]);

    if(__APP_SETTINGS__['PaymentGateways_enabled']) {
        Route::get("/settings/payments", "CoreAdmin@payment_gateways",[
            // 'permission' => 'API_manage_keys',
            'anchor' => [
                'name' => "Payments",
                'icon' => 'credit-card-fast-outline'
            ],
            'navigation' => ['advanced_settings']
        ]);
    }


    // Route::get("/settings/cron", "CoreAdmin@cron_panel",[
    //     // 'permission' => 'API_manage_keys',
    //     'anchor' => [
    //         'name' => "Scheduled Jobs",
    //         'icon' => 'clock-time-eight-outline'
    //     ],
    //     'navigation' => ['advanced_settings']
    // ]);

    Route::get("/settings/api-keys/", "RemoteServices@index",[
        'permission' => 'API_manage_keys',
        'anchor' => [
            'name' => "API Keys",
            'icon' => 'api',
            'icon_color' => 'linear-gradient(#e30000, #ffd033)'
        ],
        'navigation' => ['advanced_settings']
    ]);

    Route::get('/settings/api-keys/{name}', "RemoteServices@editor",[
        'permission' => 'API_manage_keys',
    ]);

    
    if(__APP_SETTINGS__['Enable_database_import_export'] === true) {
        Route::get("/database/", "DBMgmt@ui", [
            'permission' => 'Database_database_export',
            'anchor' => [
                'name' => 'DB Management',
                'icon' => 'database-arrow-up-outline',
            ],
            'navigation' => ['advanced_settings']
        ]);
    }

Route::get("/integrations/", "IntegrationsController@index", [
    'anchor' => [
        'name' => 'Integrations',
        'icon' => 'api'
    ],
    'navigation' => ['advanced_settings']
]);
Route::get("/integrations/{class}", "IntegrationsController@token_editor");

/** 
*  ========================================================
*  ================ MISCELLANEOUS ROUTES ==================
*  ========================================================
*/

if(app("API_contact_form_enabled") && app("Contact_form_interface") === "panel") {
    get_controller("ContactForm")::admin(null, [
        'index' => [
            'anchor' => [
                'name' => "Contact Form",
                'icon' => 'chat-alert-outline',
            ],
            'navigation' => ['admin_panel'],
            'unread' => function () {
                return (new ContactManager())->get_unread_count_for_user(session());
            },
            'handler' => '/core/contact-form.js'
        ]
    ]);
    // Route::get("/contact-form/", "ContactForm@__index", [
    //     'permission' => 'Contact_form_submissions_access',
    //     'anchor' => [
    //         'name' => "Contact Form",
    //         'icon' => 'chat-alert-outline',
    //     ],
    //     'navigation' => ['admin_panel'],
    //     'unread' => function () {
    //         return (new ContactManager())->get_unread_count_for_user(session());
    //     },
    //     'handler' => '/core/contact-form.js'
    // ]);
    Route::get("/contact-form/{id}", "ContactForm@read", ['permission' => 'Contact_form_submissions_access']);
}

