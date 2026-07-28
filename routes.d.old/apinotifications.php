<?php

use Routes\Route;

$rt = "\\Cobalt\\Notifications\\Controllers\\Notifications";

Route::s_get("/resources/vapid-pub-key", "$rt@vapid_pub_key");
Route::s_put("/push/test",  "$rt@push_test");

Route::s_get("/me/",             "$rt@getUserNotifications");
Route::s_get("/me/unread-count", "$rt@getUserNotificationCount");
Route::s_get("/addressees/",     "$rt@addressees", ['permissions' => 'Addressee_query']);
Route::s_post("/send",           "$rt@sendNotification");
Route::s_get("/{id}/for/",       "$rt@addresseeList", ['permissions' => 'Addressee_query']);
Route::s_put("/{id}/state/",     "$rt@state");
Route::s_put("/{id}/update",     "$rt@update");
Route::s_delete("/{id}/delete",  "$rt@delete_one");
Route::s_get("/{id}/update",     "$rt@edit_notification", ['permissions' => 'Notifications_can_access_any_notification']);
Route::s_get("/{id}",            "$rt@one_notification");
// Route::s_delete("/notification/{id}/untag", "$rt@untag");
unset($rt);