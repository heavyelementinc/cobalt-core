<?php

use Routes\Route;

Route::s_get("/me/",             "\\Cobalt\\Notifications\\Controllers\\Notifications@getUserNotifications");
Route::s_get("/me/unread-count", "\\Cobalt\\Notifications\\Controllers\\Notifications@getUserNotificationCount");
Route::s_get("/addressees/",     "\\Cobalt\\Notifications\\Controllers\\Notifications@addressees", ['permissions' => 'Addressee_query']);
Route::s_post("/send",           "\\Cobalt\\Notifications\\Controllers\\Notifications@sendNotification");
Route::s_get("/{id}/for/",       "\\Cobalt\\Notifications\\Controllers\\Notifications@addresseeList", ['permissions' => 'Addressee_query']);
Route::s_put("/{id}/state/",     "\\Cobalt\\Notifications\\Controllers\\Notifications@state");
Route::s_put("/{id}/update",     "\\Cobalt\\Notifications\\Controllers\\Notifications@update");
Route::s_delete("/{id}/delete",  "\\Cobalt\\Notifications\\Controllers\\Notifications@delete_one");

Route::s_get("/{id}/update",     "\\Cobalt\\Notifications\\Controllers\\Notifications@edit_notification", ['permissions' => 'Notifications_can_access_any_notification']);
Route::s_get("/{id}",            "\\Cobalt\\Notifications\\Controllers\\Notifications@one_notification");
// Route::s_delete("/notification/{id}/untag", "Notifications@untag");
