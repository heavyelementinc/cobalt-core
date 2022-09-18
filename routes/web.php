<?php

use Routes\Route;

if(app("UGC_enable_user_generated_content")) {
    Route::get(trim_trailing_slash(app("UGC_retrieval_endpoint")) . "/{file_id}", "UGC@retrieve");
}

Route::get("/", "Pages@index", [
    'anchor' => ['name' => 'Home'],
    'navigation' => ['main_navigation']
]);

Route::get("/res/fs/...","FileController@download");



if(__APP_SETTINGS__['Posts']['default_enabled']) {
    Route::get(__APP_SETTINGS__['Posts']['public_index'], "Posts@index", __APP_SETTINGS__['Posts']['public_index_options']);
    Route::get(__APP_SETTINGS__['Posts']['public_post'],  "Posts@post",  __APP_SETTINGS__['Posts']['public_post_options']);
    Route::get("/posts/{url_slug}/attachment/{filename}", "Posts@downloadFile");
}

/** If authentications are enabled, these routes should be added to the table */
if (app("Auth_logins_enabled")) {
    /** Basic login page */
    Route::get(app("Auth_login_page"), "Login@login");
    // Route::get("/preferences/password-reset-required/", "UserAccounts@change_my_password");
    // /** Admin panel (TODO: Implement admin panel) */
    // Route::get(app("Admin_panel_prefix"), "CoreController@admin_panel",['permission' => 'Admin_panel_access']);

    Route::get("/user/menu", "UserAccounts@get_user_menu");
    Route::get("/admin", "CoreController@admin_redirect");
}

if (app("Auth_account_creation_enabled")) {
    Route::get(app("Auth_onboading_url"), "UserAccounts@onboarding");
}