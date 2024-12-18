<?php

use Cobalt\Requests\Remote\Twitter;
use Cobalt\Requests\Remote\YouTube;
use Cobalt\Style\SchemeGenerator;
use Exceptions\HTTP\BadRequest;
use Render\Render;
use MikeAlmond\Color;
use Routes\RouteGroup;

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

    function settings() {
        $GLOBALS['app']->getSettingDefinitions();
        
        $settings = $GLOBALS['app']->__settings;
        $stored = $GLOBALS['app']->fetchModifiedSettings();
        $definitions = $GLOBALS['app']->definitions;
        // $definitions['js-web'] = [];
        // $definitions['js-admin'] = [];
        // $definitions['css-web'] = [];
        // $definitions['css-admin'] = [];
        // $definitions['vars-web'] = [];
        // $definitions['vars-admin'] = [];
        // $definitions['root-style'] = [];
        $order = array_unique(array_merge(array_keys($definitions), array_keys((array)$settings)));

        $buttons = "";
        $pages = "";
        $directive_abbr = [
            'alias' => 'A',
            'env' => 'E',
            'prepend' => 'P',
            'merge' => "m",
            'mergeAll' => "M",
            'push' => 'p',
            'style' => 'S'
        ];

        foreach($order as $setting) {
            $data = $definitions[$setting] ?? [];
            $cached_value = $settings[$setting];
            $directives = "";
            $alias_value = "";
            foreach($definitions[$setting]['directives'] ?? [] as $dir => $d) {
                $alias_value = "N/A";
                if(key_exists($dir, $directive_abbr)) $directives .= $directive_abbr[$dir] . ",";
                if($dir === "alias") {
                    $alias_value = json_encode($settings[$setting]) . "<br><cite style='opacity:.3'>$d</cite>";
                }
            }
            $directives = substr($directives,0,-1);
            $filename = str_replace([__ENV_ROOT__, __APP_ROOT__],['__ENV__', '__APP__'],$data['defined']);
            $source = "<span class='source env'>ENV</span>";
            if(strpos($filename, "__APP__") === 0) $source = "<span class='source app'>APP</span>";
            $buttons .= "<a href='#$setting'>$source<span class='fake-link'>$setting</span><span class='directives'>$directives</span></a>";
            $shorthand = $data['shorthand'];
            unset($definitions[$setting]['defined']);
            unset($definitions[$setting]['shorthand']);
            $pages .= view('/debug/settings/inspector.html',
                [
                    'name' => $setting,
                    'cached_value' => json_encode($cached_value, JSON_PRETTY_PRINT),
                    'stored' => json_encode($stored[$setting] ?? "",JSON_PRETTY_PRINT),
                    'aliased' => $alias_value,
                    'default' => json_encode($GLOBALS['app']->default_values[$setting] ?? $data['default'] ?? $data['meta']['merge'] ?? $data['meta']['mergeAll'],JSON_PRETTY_PRINT),
                    'definition' => "<span>".substr(
                        implode(
                            "</span>\n<span>",explode(
                                "\n",
                                htmlspecialchars(json_encode($definitions[$setting],JSON_PRETTY_PRINT))
                            )
                        ),0
                    ) . "</span>",
                    'defined_in' => $filename,
                    'shorthand' => json_encode($shorthand),
                ]
            );
        }

        add_vars([
            'title' => 'Settings Inspector',
            'buttons' => $buttons,
            'pages' => $pages,
        ]);
        
        set_template("/debug/settings/container.html");
    }

    function new_form_request() {
        add_vars([
            'title' => 'New Form Request Test'
        ]);

        return set_template('/debug/new-form-request.html');
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
            'pronoun_set' => "1",
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
        header("X-Status: @key $_POST[name] was validated");
        update("#result", ['innerHTML' => $_POST['name']." was validated"]);
        // update('input[name="name"]:closest(new-form-request)', ['remove' => true]);
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

    function x_modal() {
        $body = json_encode([
            "body" => "This is a <strong>body test</strong>"
        ]);
        header("X-Modal: $body");
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

    function s3_test() {
        //https://www.milmike.com/run-php-asynchronously-in-own-threads
        $manager = new \Files\UploadManager($_FILES);

        $manager->move_to_bucket($_FILES['file']['tmp_name'][0],"test","subscribetome-cdn");

        $manager->generate_thumbnails_exec();
        return "success";
    }

    function slow_error() {
        sleep(3);
        throw new BadRequest("You requested an error, you got one.");
    }

    function colors($color = "fe329e") {
        $cobalt = new SchemeGenerator($color);

        $style_values = $cobalt->derive_style_colors_from_accent();


        $color = \MikeAlmond\Color\Color::fromHex($color);
        $generator = new \MikeAlmond\Color\PaletteGenerator($color);
        $luminance = $generator->monochromatic(10);
        $adjacent  = $generator->adjacent();
        $palette   = $generator->tetrad();

        $html  = $this->color_sample($luminance, 'l');
        $html .= $this->color_sample($adjacent,  'a');
        $html .= $this->color_sample($palette,   'p');


        add_vars([
            'accent' => $color,
            'swatch' => $cobalt->get_swatch_divs(),
            'palettegenerator' => $html,
            'cobalt' => $style_values,
        ]);
        set_template("/debug/colors.html");
    }

    private function color_sample($colors, $label) {
        $html = "<div class='hbox'>";
        foreach ($colors as $i => $c) {
            $color = $c->getHex();
            $text = $c->getMatchingTextColor()->getHex();
            $html .= "<div style='height:200px;width:200px;background:#$color'><span style='color:#$text'>$label</span></div>";
        }

        $html .= "</div>";
        return $html;
    }

    function style_test($c = null) {
        $color = new \Cobalt\Style\Color($c ?? "#EF0D1A");
        $color->set_mode("light");
        add_vars([
            'title' => 'Style Test',
            'accent' => $color->get_color_hex(),
            'border' => $color->derive_border_color(),
        ]);

        set_template("/debug/style-test.html");
    }

    function dump() {
        var_dump($GLOBALS);
        add_vars([
            'main' => "This is a test"
        ]);
        set_template("/parts/main.html");
    }

    function next_request_post() {
        return $this->next_request("put");
    }

    function next_request_put() {
            return $this->next_request("post");
    }

    function next_request($next) {
        header("X-Next-Request: " . json_encode(['method' => $next, 'action' => "/api/v1/debug/next-request"]));
        return "Success";
    }

    function next_request_page() {
        set_template("debug/next-request.html");
    }

    function async_button() {
        set_template("debug/async-button.html");
    }

    function drag_drop() {
        set_template("debug/sortable.html");
    }

    function twitter() {
        
        add_vars([
            'title' => 'Twitter',
            'user_result' => json_encode((new Twitter())->getSingleUserPublicDataById('2789614097'),JSON_PRETTY_PRINT),
            'tweet_result' => json_encode((new Twitter())->getTweetPublicData(['1546477868506652673']),JSON_PRETTY_PRINT),
            // 'api_result' => json_encode((new Twitter())->getManyUserDataByUsername(['gardiner_bryant']),JSON_PRETTY_PRINT),
        ]);
        set_template('debug/api/twitter.html');
    }

    function youtube() {
        add_vars([
            'title' => 'YouTube',
            'user_result'   => json_encode((new YouTube())->getChannelDataById('UCv1Kcz-CuGM6mxzL3B1_Eiw'),JSON_PRETTY_PRINT),
            'tweet_result'  => json_encode((new YouTube())->getChannelDataById('UCf8uu3IE42b6hRUusufEH8g'),JSON_PRETTY_PRINT), //   UCnuvP0behGQMR5Wd3l80IWg
            // 'tweet_result'  => json_encode((new YouTube())->getTweetPublicData(['1546477868506652673']),JSON_PRETTY_PRINT),
            // 'api_result' => json_encode((new YouTube())->getManyUserDataByUsername(['gardiner_bryant']),JSON_PRETTY_PRINT),
        ]);
        set_template('debug/api/twitter.html');
    }


    function assoc_test(){
        $array = [
            "alpha",
            "beta",
            "zeta",
            "delta",
            "alpha",
            "beta",
            "zeta",
            "delta"
        ];
        
        $assoc = [
            "alpha" => "alpha",
            "beta"  => "beta",
            "zeta"  => "zeta",
            "delta" => "delta",
            "gamma" => "alpha",
            "omicron"  => "beta",
            "upsilon"  => "zeta",
            "omega" => "delta"
        ];

        $mixed = [
            "alpha" => "alpha",
            "beta"  => "beta",
            "zeta"  => "zeta",
            "delta" => "delta",
            "0" => "alpha",
            "1"  => "beta",
            "2"  => "zeta",
            3 => "delta"
        ];

        $nonSequential = [
            1 => "alpha",
            2  => "beta",
            3  => "zeta",
            5 => "delta",
            8 => "alpha",
            9  => "beta",
            10  => "zeta",
            12 => "delta"
        ];
        
        echo "Array <code>(should be false)</code>";
        var_dump(is_associative_array($array));
        
        echo "Associative <code>(should be true)</code>";
        var_dump(is_associative_array($assoc));
        
        echo "Mixed <code>(should be true)</code>";
        var_dump(is_associative_array($mixed));
        
        echo "Non-sequential <code>(should be false)</code>";
        var_dump(is_associative_array($nonSequential));
        exit;
    }




    function phpinfo() {
        if(!is_root()) kill("You don't have permisison.");
        phpinfo();
        exit;
    }

    function credit_card() {
        add_vars([
            'title' => "Credit Card Test",
            'main' => credit_card_form(),
            'shipping' => credit_card_form([], true)
        ]);
        set_template("/debug/credit-card.html");
    }

    function doc_test() {
        $prepStart = microtime(true) * 1000;
        $million = [];
        for($i = 0; $i <= 1000000; $i++){
            array_push($million, random_string(10));
        }
        $prepEnd = microtime(true) * 1000;
        // $doc = $GLOBALS['application']->allSettings;
        
        $timeAStart = microtime(true) * 1000;
        doc_to_array($million);
        $timeAEnd = microtime(true) * 1000;

        function mongo_doc_to_array($doc) {
            return json_decode(json_encode($doc),true);
        }

        $timeBStart = microtime(true) * 1000;
        mongo_doc_to_array($million);
        $timeBEnd = microtime(true) * 1000;

        $deltaPrep = $prepEnd - $prepStart;
        $deltaA = $timeAEnd - $timeAStart;
        $deltaB = $timeBEnd - $timeBStart;

        add_vars([
            'title' => "Doc Test",
            'main' => "<pre>Prep took $deltaPrep ms\ndoc_to_array took $deltaA ms\nmongo_doc_to_array took $deltaB ms</pre>"
        ]);

        return set_template("/parts/main.html");
    }

    function status_modal() {
        add_vars(['title' => "Server Control Headers"]);

        return set_template("/debug/server-control-headers.html");
    }

    function control_headers($method = null) {

        switch($method) {
            case "confirm":
                return $this->confirm_test();
            case "revalidate":
                return $this->revalidate_test();
        }

        switch($_POST['method']) {
            case "modal":
                $method = "X-Modal:";
                break;
            case "status":
            default:
                $method = "X-Status:";
                break;
        }

        switch($_POST['class']) {
            case "warning":
                header("HTTP/1.0 400 Bad Request");
                break;
            case "error":
                // header("HTTP/1.0 500 Error");
                break;
            case "message":
            default:
                header("HTTP/1.0 200 OK");
                break;
        }

        $messages = [
            "Parlay.",
            "You need to find yourself a girl, mate.",
            "I'm dishonest, and a dishonest man you can always trust to be dishonest.",
            "If you were waiting for the opportune moment, that was it.",
            "I'm disinclined to acquiesce to your request.",
            "Savvy?"
        ];

        $message = $messages[rand(0, count($messages) - 1)];

        header("$method @$_POST[type] $message");

        return ['message' => $message];
    }

    private function confirm_test(){
        update("#confirm-confirmation", ['innerHTML' => 'Confirmation pending...']);
        confirm("Are you sure you want to complete this test?", $_POST);

        update('#confirm-confirmation', ['innerHTML' => 'You took the action']);
        return 1;
    }

    private function revalidate_test(){
        update("#revalidate-confirmation", ['innerHTML' => 'Revalidation pending...']);
        reauthorize("Are you sure you want to complete this test?", $_POST);

        update('#revalidate-confirmation', ['innerHTML' => 'You validated yourself']);
        return 1;
    }

    function new_route_group() {
        $route_group = [
            "<h2>Basic</h2>",
            (new RouteGroup("main_navigation", ""))->render(),
            "<h2>ID test <code>test-id</code></h2>",
            (new RouteGroup("admin_panel", ""))->setID("test-id")->render(),
            "<h2>Classes <code>class-test</code></h2>",
            (new RouteGroup("advanced_settings", "", true))->setClasses(['class-test'])->render(),
            "<h2>Icon Panel</h2>",
            (new RouteGroup("advanced_settings", "", true))->setIconPanel(true)->render(),
            "<h2>Classes from string</h2>",
            (new RouteGroup("application_settings", ""))->setClassesFromString("class-test other-class")->render(),
            "<h2>Render Depth: 2</h2>",
            (new RouteGroup("debug_demo", "", false))->setSubmenuDepth(2)->setSubmenuVisibilty(true)->render(),
            "<h2>External Link Test</h2>",
        ];

        $route_group[] = (new RouteGroup("presentation_settings", ""))->setExternalLinks([
            [
                'href'  => 'https://heavyelement.io/',
                'label' => 'Heavy Element',
                'order' => 1
            ]
        ])->render();

        $route_group[] = "<h2>Excluded Wrappers</h2>";
        $route_group[] = (new RouteGroup("presentation_settings", ""))->setExcludeWrappers(true)->render();
        add_vars([
            'title' => 'Route Group Test',
            'main' => implode("",$route_group),
        ]);
        return set_template("/debug/route-group-test.html");
    }
}
