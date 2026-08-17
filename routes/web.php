<?php

use Cobalt\Auth\Users\Controllers\Users;
use Components\ServiceAreas\Controllers\Towns;
use Routes\Options;
use Routes\Route;

if(app("UGC_enable_user_generated_content")) {
    Route::get(new Options(trim_trailing_slash(app("UGC_retrieval_endpoint")) . "/{file_id}", "UGC@retrieve"));
}

Route::get(new Options("/", "Pages@index", __APP_SETTINGS__['Landing_page_home_route_options']));

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
    Route::get('/events', "Cobalt\\EventListings\\Controllers\\Events@public_index",[
        'anchor'     => ['name' => 'Events'],
        'navigation' => ['main_navigation']
    ]);
    Route::get("/events/{id}.ics", "Cobalt\\EventListings\\Controllers\\Events@iCalEvent");
    Route::get("/events/{id}",     "Cobalt\\EventListings\\Controllers\\Events@public_listing");
}

/** If authentications are enabled, these routes should be added to the table */
if (app("Auth_logins_enabled")) {
    // Redirect anyone coming to the "/admin" page (without a trailing slash)
    Route::get("/admin", "CoreController@admin_redirect");
    Users::get((new Options('/login/{id}/{nonce}', 'login_link')));
    Users::get((new Options('/login/', 'login_form')));
}

if (__APP_SETTINGS__['Contact_form_public_routes_enabled'] && __APP_SETTINGS__['API_contact_form_enabled']) {
    Route::get((new Options("/contact/", "Cobalt\ContactForm\Controllers\PublicContact@form"))
        ->set_handler(__APP_ROOT__.'/Pages/Contact/handlers/contact.js')
        ->set_navigation(__APP_SETTINGS__['Contact_form_navigation_options'])
    );

    Route::get((new Options("/contact/finish", "Cobalt\ContactForm\Controllers\PublicContact@submission_success"))
        ->set_sitemap(['ignore' => true])
    );
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

if(__APP_SETTINGS__['ServiceAreas_enabled']) {
    Towns::get((new Options('/services/{area}', 'townListing')));
}
