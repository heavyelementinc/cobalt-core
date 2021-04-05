<?php

/** Protected JavaScript content. TODO: Ensure this content is only accessible during debug */
Routes\Route::get("/js/...","FileController@javascript");
Routes\Route::get("/css/...","FileController@css");
if(app('enable_core_content')){
    /** Core content includes stuff in the __ENV_ROOT__/shared/ directory */
    Routes\Route::get("/...","FileController@locate");
}