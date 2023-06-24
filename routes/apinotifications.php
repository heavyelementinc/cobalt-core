<?php

use Routes\Route;

Route::s_get("/me/",             "Notifications@getUserNotifications");
Route::s_get("/me/unread-count", "Notifications@getUserNotificationCount");
Route::s_get("/addressees/",     "Notifications@addressees", ['permissions' => 'Addressee_query']);
Route::s_post("/send",           "Notifications@sendNotification");
Route::s_put("/{id}/status/{state}", "Notifications@status");
Route::s_put("/{id}/update",     "Notifications@update");
Route::s_delete("/{id}/delete",  "Notifications@delete_one");

Route::s_get("/{id}/update",     "Notifications@edit_notification", ['permissions' => 'Notifications_can_access_any_notification']);
Route::s_get("/{id}",            "Notifications@one_notification");
// Route::s_delete("/notification/{id}/untag", "Notifications@untag");
