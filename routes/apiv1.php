<?php

use Cobalt\Auth\Users\Controllers\Users;
use Cobalt\ContactForm\Controllers\Submissions;
use Cobalt\Documentation\Controllers\Documentation;
use Cobalt\EventListings\Controllers\Events;
use Cobalt\Settings\Controllers\Settings;
use Components\StructuredData\Controllers\BusinessDetails;
use Routes\Options;
use Routes\Route;

Route::get("/ping", "CoreApi@ping");

if(app("UGC_enable_user_generated_content")) {
    Route::post(app("UGC_submit_endpoint"), "UGC@submit");
}

/** API routes for authorization */
if (app('Auth_logins_enabled')) {
    Users::apiv1();
    Users::post((new Options('/login', 'api_login_handler')));
    Users::get((new Options("/logout", "api_logout")));
    Users::get((new Options("/session/authenticated/", "api_list_authenticated_users")));
    Users::put((new Options("/session/switch/{index}", "api_switch_to_authenticated_user")));
    Users::delete((new Options("/session/{id}/delete/", "delete_session")));
}

if (app('Web_main_content_via_api')) {
    Route::get("/page", "CoreApi@page");
}

if (app('API_contact_form_enabled')) {
    Submissions::apiv1();
    Route::post("/contact", "Cobalt\\ContactForm\\Controllers\\Submissions@public_form_submission");

}

if (app("CobaltEvents_enabled")) {
    Events::apiv1();
}


Route::s_post("/extensions/{uuid}/info",    "ExtensionsController@modify_extension_state",   ['permission' => 'Extensions_allow_management']);
Route::s_post("/extensions/{uuid}/options", "ExtensionsController@modify_extension_options", ['permission' => 'Extensions_allow_management']);
Route::s_post("/extensions/rebuild", "ExtensionsController@rebuild_database", ['permission' => 'Extensions_allow_management']);

Route::s_post("/integrations/{id}/update", "IntegrationsController@update");
Route::s_delete("/integrations/{id}/reset", "IntegrationsController@delete");

if (app('debug')) {
    Route::get("/hello_world/{something}/{machina}?", "HelloWorld@do_it", [ // Hello World test route
        'requires_csrf' => false,
        'requires_cors' => false,
        'permission' => 'Auth_allow_editing_users'
    ]);
}

if(app("enable_debug_routes")) {
    Route::post("/debug/exception/{type}?", "DebugError@api_throw_error");
    Route::put("/debug/control-headers/...", "Debug@control_headers");

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

    Route::get("/header-tests/{response}", "DebugHeaders@response");
    Route::post("/proto/", "SchemaDebug@filter_test");
}

if(app("Mailchimp_default_list_id")) {
    Route::post("/newsletter/onboard", "Mailchimp@onboarding");
}


// Route::s_put("/api/key/{service}",  "APIManagement@update", ['permission' => 'API_manage_keys']);
Route::s_post("/remote/{service}/update", "RemoteServices@update",  ['permission' => 'API_manage_keys']);

if(app("Customizations_enabled")) {
    Route::s_post("/customizations/update/{id}?", "Customizations@update", ['permission' => 'Customizations_modify']);
    Route::s_post("/customizations/upload/{id}?", "Customizations@uploadFile", ['permission' => 'Customizations_modify']);
    Route::s_put("/customizations/reset/all", "Customizations@resetAll", ['permission' => 'Customizations_modify']);
    Route::s_put("/customizations/reset/{id}", "Customizations@resetItem", ['permission' => 'Customizations_modify']);
    Route::s_delete("/customizations/{id}", "Customizations@deleteItem", ['permission' => 'Customizations_delete']);
    Route::s_delete("/customizations/attachment/{id}", "Customizations@delete", ['permission' => 'Customizations_delete']);
}

if(__APP_SETTINGS__['Enable_database_import_export']) {
    Route::s_post('/database/export/','DBMgmt@download', [
        'permission' => 'Database_database_export',
    ]);
}

Route::s_delete((new Options("/image-editor/{id}/{name}?","ImageEditor@delete"))
    ->set_permission("Customizations_delete")
);

Route::delete("/crudable-files/{id}", "CrudableFiles@delete_file_by_id", [
    'permission' => "Customizations_delete"
]);
Route::post("/crudable-files/{id}/rename", "CrudableFiles@renameFile", [
    'permission' => 'Customizations_modify'
]);
Route::get("/crudable-files/{id}/reset", "CrudableFiles@reset_metadata", [
    'permission' => 'Customizations_modify'
]);

Cobalt\Pages\Controllers\Posts::apiv1();
Route::s_post('/posts/{id}/preview-key/', '\\Cobalt\\Pages\\Controllers\\Posts@preview_key');
if(__APP_SETTINGS__['LandingPages_enabled']) {
    Cobalt\Pages\Controllers\LandingPages::apiv1();
    Route::s_post('/landing-pages/{id}/preview-key/', '\\Cobalt\\Pages\\Controllers\\LandingPages@preview_key');
}

if(__APP_SETTINGS__['Documentation_enable_in_userbar']) {
    Documentation::apiv1(null, []);
}

if(__APP_SETTINGS__['Block_Editor_endpoints']) {
    
    Route::s_post('/block-editor/upload/url/', "BlockEditor@fileByURL", [
        'csrf_required' => false
    ]);
    
    Route::s_post('/block-editor/upload/', "BlockEditor@fileUpload", [
        'csrf_required' => false
    ]);
    
    Route::s_get('/block-editor/link-fetch/', "BlockEditor@linkFetcher", [
        'csrf_required' => false
    ]);
}

CrudableFiles::apiv1();

Settings::post((new Options('/settings/update/', 'update'))
    ->set_permission('Auth_modify_cobalt_settings')
);

// BusinessDetails::apiv1();
// BusinessDetails::post((new Options("/settings/seo/update/", "__update"))
//     ->set_permission("Auth_modify_cobalt_settings")
// );