<?php

use Routes\Route;

Route::get("/user", "Notifications@getUserNotifications");
Route::post("/send", "Notifications@sendNotification");
