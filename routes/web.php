<?php

use Routes\Options;
use Routes\Route;

if(app("UGC_enable_user_generated_content")) {
    Route::get(new Options(trim_trailing_slash(app("UGC_retrieval_endpoint")) . "/{file_id}", "UGC@retrieve"));
}

Route::get(new Options("/", "Pages@index", __APP_SETTINGS__['Landing_page_home_route_options']));

Route::get(new Options("/res/fs/...","FileController@download"));

Route::get(new Options("/ServiceWorker.js", "FileController@service_worker"));

if(__APP_SETTINGS__['Posts_default_enabled']) {
    if(__APP_SETTINGS__['Posts_enable_rss_feed']) {
        $address = __APP_SETTINGS__['Posts_public_index'];
        $length = strlen($address) - 1;
        if($address[$length] === "/") $address = substr($address, 0, -1);
        Route::get("$address.xml", "\\Cobalt\\Pages\\Controllers\\Posts@rss_feed");
    }
    Route::get(__APP_SETTINGS__['Posts_public_index'], "\\Cobalt\\Pages\\Controllers\\Posts@posts_landing", __APP_SETTINGS__['Posts_public_index_options']);
    
    $posts = array_merge(
        __APP_SETTINGS__['Posts_public_post_options'] ?? [], [
            
    ]);
    
    Route::get(__APP_SETTINGS__['Posts_public_post'] . "...",  "\\Cobalt\\Pages\\Controllers\\Posts@page", [
        'sitemap' => [
            'ignore' => true,
            'children' => function () {
                return register_individual_post_routes();
            },
            'lastmod' => fn() => null
        ]
    ]
        // (new Options(__APP_SETTINGS__['Posts_public_post'] . "...",  "Posts@page"))
        // ->set_sitemap([
        //     'ignore' => true,
        //     'children' => function () {
        //         return register_individual_post_routes();
        //     },
        //     'lastmod' => fn() => null
        // ])
        // ->set_handler('core/post2_0-handler.js')
    );
    // Route::get("/posts/{url_slug}/attachment/{filename}", "Posts@downloadFile");
}

if(__APP_SETTINGS__['CobaltEvents_enable_public_index']) {
    Route::get('/events', "EventsController@public_index",[
        'anchor' => ['name' => 'Events'],
        'navigation' => ['main_navigation']
    ]);
}

/** If authentications are enabled, these routes should be added to the table */
if (app("Auth_logins_enabled")) {
    /** Basic login page */
    Route::get(app("Auth_login_page"), "Login@login_form");
    Route::get("/login/email", "Login@email_sent", ['sitemap' => ['ignore' => true]]);
    // Route::get("/preferences/password-reset-required/", "UserAccounts@change_my_password");
    // /** Admin panel (TODO: Implement admin panel) */
    // Route::get(app("Admin_panel_prefix"), "CoreController@admin_panel",['permission' => 'Admin_panel_access']);

    // Route::get("/user/menu", "UserAccounts@get_user_menu");
    Route::get("/admin", "CoreController@admin_redirect");
    Route::get("/login/password-reset", "Login@password_reset_initial_form", [
        'sitemap' => ['ignore' => true]
    ]);
    Route::get("/login/password-reset/{token}", "Login@password_reset_token_form");
}

if(__APP_SETTINGS__['Mailchimp_default_list_id']) {
    Route::get("/newsletter/", "Mailchimp@onboard_landing");
}

if (app("Auth_account_creation_enabled")) {
    Route::get(app("Auth_onboading_url"), "UserAccounts@onboarding");
}

if (app("Database_fs_enabled")) {
    Route::get(trim_trailing_slash(app("Database_fs_public_endpoint")) . "/...", "FileController@download");
}

Route::get("/robots.txt", "FileController@robots");
if(__APP_SETTINGS__['AI_prohibit_scraping_notice']) {
    Route::get("/ai.txt", "FileController@ai");
}
Route::get("/sitemap.xml", "FileController@sitemap");

Route::get("/auth/{id}/register", "IntegrationsController@oauth_receive");
Route::get("/auth/{id}/deauthorize","IntegrationsController@oauth_deauthorize");
Route::s_get("/me", "UserAccounts@me");

// if()
Route::s_get("/file-picker/", "CrudableFiles@file_picker", [
    'permission' => 'Customizations_modify'
]);

