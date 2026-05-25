<?php

use Cobalt\Auth\Users\Controllers\Users;
use Cobalt\ContactForm\Controllers\Submissions;
use Cobalt\EventListings\Controllers\Events;
use Cobalt\Settings\Controllers\Settings;
use Contact\ContactManager;
use Routes\Options;
use Routes\Route;
use Symfony\Component\Console\Attribute\Option;

Route::get("/", "CoreAdmin@index", [
    'name' => 'Dashboard',
    'anchor' => [
        'name' => 'Dashboard',
    ],
    'navigation' => ['admin_panel']
]);

if(__APP_SETTINGS__['Posts_default_enabled']) {
    Cobalt\Pages\Controllers\Posts::admin();
    // Route::get("/posts/", "Posts@index",[
    //     'anchor' => ['name' => __APP_SETTINGS__['Posts_default_name']],
    //     'navigation' => ['admin_panel'],
    // ]);
    // Route::get("/posts/{id}?", "Posts@edit",[
    //     // 'handler' => "core/posts.js",
    // ]);
}

if(__APP_SETTINGS__['LandingPages_enabled']) {
    \Cobalt\Pages\Controllers\LandingPages::admin();
}

if(__APP_SETTINGS__['Documentation_enable_in_userbar']) {
    \Cobalt\Documentation\Controllers\Documentation::admin();
}


/** ========================================================
 *  ========================================================
 *  ================== CONTROL PANEL =======================
 *  ========================================================
 *  ========================================================
 * 
 */


    // Route::get("/settings/", "CoreAdmin@settings_index", [
    //     'anchor' => ['name' => 'Cobalt Settings', 'icon' => 'gear']
    // ]);

    /** Control Panel and Settings Editor */
    Settings::get((new Options("/settings/", 'settings_index'))
        // ->set_navigation([
        //     [
        //         'name' => 'Cobalt Settings', 
        //         'icon' => 'gear'
        //     ]
        // ])
    );

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
        Events::admin();
        // Route::get("/cobalt-events/edit/{id}?", "EventsController@edit_event", [
        //     'handler' => 'core/events.js',
        //     'permission' => "CobaltEvents_crud_events"
        // ]);
        // Route::get("/cobalt-events/?...?", "EventsController@list_events", [
        //     'permission' => "CobaltEvents_crud_events",
        //     'anchor' => [
        //         'name' => 'Event Manager',
        //         'href' => '/cobalt-events/',
        //         'icon' => 'information-outline',
        //         'icon_color' => 'linear-gradient(0.5turn, #14BDEB, #9d3cf6 80%)',
        //     ],
        //     'navigation' => ['admin_panel', 'presentation_settings']
        // ]);
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
        // Route::get("/me/", "UserAccounts@me",
        //     [
                
        //     ]
        // );
        Users::get((new Options('/me/', 'userSelfService')));
        Users::admin(options: [
            'index' => [
                'permission' => 'Auth_allow_editing_users',
                'navigation' => [
                    'application_settings' => [
                        'name' => 'Users',
                        'icon' => 'account-group-outline',
                        'icon_color' => '#FF5964',
                    ]
                ],
            ]
        ]);
        // CoreUserAccounts::admin(null, [
        //     'index' => [
        //         'permission' => 'Auth_allow_editing_users',
        //         'anchor' => [
        //             'name' => 'Users',
        //             'icon' => 'account-group-outline',
        //             'icon_color' => '#FF5964'
        //         ],
        //         'navigation' => ['application_settings']
        //     ],
        //     'edit' => [
        //         'permission' => 'Auth_allow_editing_users',
        //     ]
        // ]);
    }

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

if(app("API_contact_form_enabled") && __APP_SETTINGS__["Contact_form_on_success_modes"] & CONTACT_SUCCESS_SYSTEM) {
    // Submissions::admin(null, [
    //     'anchor' => 'Contact Form',
    // ]);
    // ContactForm::admin(null, [
    //     'index' => [
    //         'anchor' => [
    //             'name' => "Contact Form",
    //             'icon' => 'chat-alert-outline',
    //         ],
    //         'navigation' => ['admin_panel'],
    //         'unread' => function () {
    //             return (new ContactManager())->get_unread_count_for_user(session());
    //         },
    //         'handler' => '/core/contact-form.js'
    //     ]
    // ]);
    Submissions::admin(null, [
        // 'index' => [
        //     'anchor' => [
        //         'name' => "Contact Form",
        //         'icon' => 'chat-alert-outline',
        //     ],
        //     'navigation' => ['admin_panel'],
        //     // 'unread' => function () {
        //     //     return (new ContactManager())->get_unread_count_for_user(session());
        //     // },
        //     // 'handler' => '/core/contact-form.js'
        // ]
    ]);
    // Route::get("/contact-form/{id}", "ContactForm@read", ['permission' => 'Contact_form_submissions_access']);
}

