<?php

use Routes\Route;

Route::get("/token/{token}", "Login@handle_token_auth");

if(__APP_SETTINGS__['Webmentions_enable_recieving']) {
    Route::post("/linkback/", "Webhooks@linkback", ['csrf_required' => false]);
    // $csrf_token = "?".CSRF_INCOMING_FIELD."=".csrf_get_token();
    header("Link: <".server_name() . "/webhooks/linkback/>; rel=\"webmention\"");
}