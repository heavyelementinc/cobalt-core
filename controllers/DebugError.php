<?php

use Exceptions\HTTP\Confirm;

class DebugError {
    function index() {
        $class_dir = "/classes/Exceptions/HTTP/";
        $app_dir = __APP_ROOT__ . $class_dir;
        $env_dir = __ENV_ROOT__ . $class_dir;

        $app = [];
        if(file_exists($app_dir)) $app = scandir($app_dir);
        $env = scandir($env_dir);

        $exceptions = [...$app, ...$env];
        $filter = [".",".."];
        $types = [
            'button' => "",
            'anchor' => "",
            'form' => "",
            'option' => "",
        ];
        foreach($exceptions as $exception) {
            if(in_array($exception,$filter)) continue;
            $generative = $this->generate_exception_button_link($exception);
            $types['button'] .= $generative[0];
            $types['anchor'] .= $generative[1];
            $types['form'] .= $generative[2];
            $types['option'] .= $generative[3];
        }
        add_vars(array_merge([
            'titles' => "Exceptions debugging",
        ],$types));
        return set_template("/debug/exceptions.html");
    }

    private function generate_exception_button_link($file) {
        $masks = [__APP_ROOT__ . "/classes/Exceptions/HTTP/", __APP_ROOT__ . "/classes/Exceptions/HTTP/", ".php"];
        $name = str_replace($masks, "", $file);
        return [
            "<async-button method='POST' action='/api/v1/debug/exception/$name'>$name</async-button>",
            "<li><a href='/debug/exception/$name'>$name</a></li>",
            "<form-request method='POST' action='/api/v1/debug/exception/'><fieldset><legend>$name</legend><input type='text' name='type' value='$name'><button type='submit'>Submit</button></fieldset></form-request>",
            "<option method='POST' action='/api/v1/debug/exception/$name'>$name</option>",
        ];
    }

    function api_throw_error($type = null) {
        if($type === null) $type = $_POST['type'];
        if($type === "Confirm") {
            confirm("This is a confirmation message.", ['test' => 'test'], true);
            return;
        }
        $namespaced = "\\Exceptions\\HTTP\\$type";
        throw new $namespaced("This is a test '$type' exception");
    }
}
