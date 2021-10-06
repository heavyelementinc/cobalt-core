<?php

use Exceptions\HTTP\BadRequest;

class Debug extends \Controllers\Pages {

    function debug_directory() {
        add_vars(['title' => "Debug Directories"]);
        set_template("/debug/debug.html");
    }
    function debug_renderer() {
        add_vars([
            'title' => "Welcome to Cobalt!",
            'mustache' => "<b>This is a mustache</b>",
            'tictac' => "<i>This is a tictac</i>",
            'bools' => ['true' => true, 'false' => false],
            'lookup' => ['field' => "LOOKUP"],
            'object' => json_decode('{"property":"value"}'),
        ]);
        set_template("debug/renderer.html");
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
        set_template("/parts/main.html");
    }

    function debug_slideshow() {
        add_vars(['title' => 'Slideshow Test']);
        set_template('/debug/slideshow.html');
    }

    function debug_inputs() {
        add_vars(['title' => 'Input Test']);
        set_template("/debug/inputs.html");
    }

    function debug_parallax() {
        add_vars([
            'title' => 'Parallax Test',
            'body_class' => "cobalt-parallax--container"
        ]);
        set_template("/debug/parallax.html");
    }

    function debug_loading() {
        add_vars([
            'title' => 'Loading test'
        ]);
        set_template("/debug/loading.html");
    }

    function debug_calendar($date = null) {
        if ($date === null) $date = time();
        $calendar = new \Calendar\Calendar($date);
        $show_debug_info = function ($calendar) {
            return "Calendar type: <b>" . $calendar->get_calendar_type() . "</b> | " .
                "First cell: <b>" . date("Y-m-d", $calendar->get_first_cell_timestamp()) . "</b> | " .
                "Target cell: <b>" . date("Y-m-d", $calendar->get_timestamp()) . "</b> | " .
                "Last cell: <b>" . date("Y-m-d", $calendar->get_last_cell_timestamp()) . "</b>";
        };
        add_vars([
            'title' => 'Calendar test',
            'main' => $calendar->render("day") . $show_debug_info($calendar) .
                $calendar->render("week") . $show_debug_info($calendar) .
                $calendar->render() . $show_debug_info($calendar) .
                $calendar->render("rolling") . $show_debug_info($calendar)
        ]);
        set_template("/debug/calendar.html");
    }

    function flex_table() {
        add_vars([
            'title' => 'Flex Table Test',
        ]);
        set_template("/debug/flex-table.html");
    }

    function relative_path_test() {
        add_vars(
            [
                'title' => 'Relative Path',
                'anchor' => "<a href='$GLOBALS[PATH]test'>Test<a>"
            ]
        );
        set_template("/debug/relative_path_test.html");
    }

    function form_test() {
        $document = new \Validation\ExampleSchema([
            'name' => "Terry Testalot",
            'email' => "terry_t@heavyelement.io",
            'phone' => '5554041122',
            'region' => ['us-west'],
            'order_count' => 5,
            'test' => [
                ['foo' => 'Test Data',],
                ['foo' => 'Test Number 2',],
                ['bar' => 'Baz test',]
            ]
        ]);
        add_vars([
            'title' => 'Form Validation Test',
            'document' => $document
        ]);
        set_template(("/debug/validator.html"));
    }

    function validate_test_form() {
        $validator = new \Validation\ExampleSchema();

        $result = $validator->__validate($_POST);

        return $result;
    }

    function confirm_test_form() {
        confirm("Are you sure?", $_POST);
        return [
            'confirm' => "Confirmation that confirm works, confirm!"
        ];
    }

    function modal_test() {
        add_vars([
            'title' => 'Modal Test'
        ]);

        set_template("/debug/modal-template.html");
    }

    function slow_response($delay = 10) {
        $delay = (int)$delay;
        if ($delay > 30) $delay = 30;
        if ($delay < 1) $delay = 1;
        sleep($delay);
        add_vars([
            'title' => 'Slow Response Simulation',
            'delay' => $delay
        ]);
        set_template("/debug/slow-response.html");
    }

    function action_menu() {
        add_vars([
            'title' => "Action Menu test"
        ]);
        set_template("/debug/action_menu.html");
    }

    function async_wizard() {
        add_vars([
            'title' => "Async Wizard test"
        ]);
        set_template("/debug/async-wizard.html");
    }

    function environment() {
        add_vars([
            'title' => "Docker Debug",
            'main' => "<pre>" . var_export(getenv(), true) . "\n" . var_export($_SERVER, true) . "</pre>"
        ]);

        set_template("/parts/main.html");
    }

    function event_stream() {
        add_vars([
            'title' => "Server-Sent Events"
        ]);

        set_template("/debug/server-events.html");
    }

    function file_upload_demo() {
        add_vars([
            'title' => "File Upload Demo"
        ]);

        set_template("/debug/file-upload.html");
    }

    function upload_test() {
        $_POST['file_count'] = count($_FILES);
        return $_POST;
    }

    function image_test() {
        //https://www.milmike.com/run-php-asynchronously-in-own-threads
        $manager = new \Files\UploadManager($_FILES);
        $dir = __APP_ROOT__ . "/ignored/tmp/cobalt-debug/";
        // $dir = "/tmp/cobalt-debug/";
        // unlink($dir . "*");
        mkdir($dir, 0777, true);
        $manager->move_all_files_to_dir($dir);

        $manager->generate_thumbnails_exec();
        return "success";
    }

    function slow_error() {
        sleep(3);
        throw new BadRequest("You requested an error, you got one.");
    }
}
