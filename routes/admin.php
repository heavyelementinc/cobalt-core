<?php

use Routes\Route;

Route::get("/?", "CoreAdmin@index", ['name' => 'Dashboard']);

/** User management interface */
Route::get("/users/", "CoreAdmin@manage_users", ['name' => "Users"]);
Route::get(app("Auth_user_manager_individual_page") . "/{user}", "CoreController@user_manager", ['handler' => 'core/user_manager.js', 'permission' => "Auth_allow_editing_users"]);
