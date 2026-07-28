<?php

use Routes\Route;
use Routes\Options;
Route::get("/fs/...", "FileController@download");
