<?php

use Routes\Route;

/** Protected JavaScript content. TODO: Ensure this content is only accessible during debug */
Route::get("/js/...", "FileController@javascript");
Route::get("/css/...", "FileController@css");
Route::get("/plugins/{plugin}/...", "FileController@plugin_resources");
Route::get("/site.webmanifest", "FileController@manifest");

if (app('enable_core_content')) {
    /** Core content includes stuff in the __ENV_ROOT__/shared/ directory */
    Route::get("/...", "FileController@core_content_shared");
}
