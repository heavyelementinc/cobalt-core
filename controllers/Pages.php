<?php

class Pages extends \Controllers\Pages{
    function index(){
        add_vars([
            'title' => "Welcome to Cobalt!",
            'test' => "<b>This is a test</b>",
            'true' => true,
            'lookup' => ['field' => "LOOKUP"],
            'object' => json_decode('{"object":"value"}'),
        ]);
        add_template("parts/index.html");
    }
}