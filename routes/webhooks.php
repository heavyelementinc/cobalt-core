<?php

use Routes\Route;

Route::get("/token/{token}", "Login@handle_token_auth");
