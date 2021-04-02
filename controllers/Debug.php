<?php
class Debug extends \Controllers\Pages{
    function debug_renderer(){
        add_vars([
            'title' => "Welcome to Cobalt!",
            'mustache' => "<b>This is a mustache</b>",
            'tictac' => "<i>This is a tictac</i>",
            'bools' => ['true' => true,'false' => false],
            'lookup' => ['field' => "LOOKUP"],
            'object' => json_decode('{"property":"value"}'),
        ]);
        add_template("debug/renderer.html");
    }
    function debug_slideshow(){
        add_vars(['title' => 'Slideshow Test']);
        add_template('/debug/slideshow.html');
    }
    function debug_inputs(){
        add_vars(['title' => 'Input Test']);
        add_template("/debug/inputs.html");
    }
}