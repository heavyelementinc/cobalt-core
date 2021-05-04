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

    function debug_router(){
        // $routes = json_encode($GLOBALS[$GLOBALS['route_table_address']],JSON_PRETTY_PRINT);

        $routes = "";
        foreach($GLOBALS[$GLOBALS['route_table_address']] as $method => $entries){
            foreach($entries as $route => $opts){
                $routes .= with("/debug/route.html", [
                    'method' => $method,
                    'route'  => $route,
                    'opts' => $opts,
                    'json' => json_encode($opts,JSON_PRETTY_PRINT)
                ]);
            }
        }
        
        add_vars([
            'title' => 'Router',
            'main' => $routes . "<style>main>section{display:flex;flex-wrap:wrap;}</style>"
        ]);
        add_template("/parts/main.html");
    }
    
    function debug_slideshow(){
        add_vars(['title' => 'Slideshow Test']);
        add_template('/debug/slideshow.html');
    }
    
    function debug_inputs(){
        add_vars(['title' => 'Input Test']);
        add_template("/debug/inputs.html");
    }

    function debug_parallax(){
        add_vars([
            'title' => 'Parallax Test',
            'body_class' => "cobalt-parallax--container"
        ]);
        add_template("/debug/parallax.html");
    }

    function debug_loading(){
        add_vars([
            'title' => 'Loading test'
        ]);
        add_template("/debug/loading.html");
    }

    function debug_calendar($date = null){
        if($date === null) $date = time();
        $meta_data = ["cell_id"=>"apr-01", "data_date"=>"1617249600", "cell_class"=>""];
        $calendar = new \Calendar\Calendar($date, $meta_data); //Change this to match class.
        add_vars([
            'title' => 'Calendar test',
            'main' => $calendar->draw() //Change this to match function.
        ]);

        add_template("/parts/main.html");
    }

    /**
     * DELETE THIS WHEN DONE!!!
     */
    function debug_calendar_example($date = null){
        if($date === null) $date = time();
        $calendar = new \Calendar\Calendar($date); //Change this to match class.
        add_vars([
            'title' => 'Calendar test',
            'main' => $calendar->draw_example() //Change this to match function.
        ]);

        add_template("/parts/main.html");
    }
}