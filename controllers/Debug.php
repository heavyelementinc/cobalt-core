<?php
class Debug extends \Controllers\Pages {
    function debug_renderer() {
        add_vars([
            'title' => "Welcome to Cobalt!",
            'mustache' => "<b>This is a mustache</b>",
            'tictac' => "<i>This is a tictac</i>",
            'bools' => ['true' => true, 'false' => false],
            'lookup' => ['field' => "LOOKUP"],
            'object' => json_decode('{"property":"value"}'),
        ]);
        add_template("debug/renderer.html");
    }

    function debug_router() {
        // $routes = json_encode($GLOBALS[$GLOBALS['ROUTE_TABLE_ADDRESS']],JSON_PRETTY_PRINT);

        $routes = "";
        foreach ($GLOBALS[$GLOBALS['ROUTE_TABLE_ADDRESS']] as $method => $entries) {
            foreach ($entries as $route => $opts) {
                $routes .= with("/debug/route.html", [
                    'method' => $method,
                    'route'  => $route,
                    'opts' => $opts,
                    'json' => json_encode($opts, JSON_PRETTY_PRINT)
                ]);
            }
        }

        add_vars([
            'title' => 'Router',
            'main' => $routes . "<style>main>section{display:flex;flex-wrap:wrap;}</style>"
        ]);
        add_template("/parts/main.html");
    }

    function debug_slideshow() {
        add_vars(['title' => 'Slideshow Test']);
        add_template('/debug/slideshow.html');
    }

    function debug_inputs() {
        add_vars(['title' => 'Input Test']);
        add_template("/debug/inputs.html");
    }

    function debug_parallax() {
        add_vars([
            'title' => 'Parallax Test',
            'body_class' => "cobalt-parallax--container"
        ]);
        add_template("/debug/parallax.html");
    }

    function debug_loading() {
        add_vars([
            'title' => 'Loading test'
        ]);
        add_template("/debug/loading.html");
    }

    function debug_calendar($date = null) {
        if ($date === null) $date = time();
        $calendar = new \Calendar\Calendar($date);
        add_vars([
            'title' => 'Calendar test',
            'main' => $calendar->render("day") . $calendar->render("week") . $calendar->render()
        ]);
        add_template("/parts/main.html");
    }

    function flex_table() {
        add_vars([
            'title' => 'Flex Table Test',
        ]);
        add_template("/debug/flex-table.html");
    }

    function relative_path_test() {
        add_vars(
            [
                'title' => 'Relative Path',
                'anchor' => "<a href='$GLOBALS[PATH]test'>Test<a>"
            ]
        );
        add_template("/debug/relative_path_test.html");
    }

    function form_test() {
        add_vars([
            'title' => 'Form Validation Test',
        ]);
        add_template(("/debug/validator.html"));
    }

    function validate_test_form() {
        $validator = new \CRUD\ExampleValidator();
        $result = $validator->__validate($_POST);

        return $result;
    }
}
