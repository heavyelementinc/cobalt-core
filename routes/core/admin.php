<?php
\Routes\Route::get("/","CoreAdmin@index",['name' => 'Dashboard']);
\Routes\Route::get("/users/","CoreAdmin@manage_users",['name' => "Users"]);
/** User management interface */
Routes\Route::get(app("Auth_user_manager_individual_page") . "/{user}","CoreController@user_manager",['handler' => 'core/user_manager.js','permission' => "Auth_allow_editing_users"]);