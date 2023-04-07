<?php

use Exceptions\HTTP\BadGateway;
use Exceptions\HTTP\NotFound;

class APIManagement {

    var $__namespace = "\\Cobalt\\Requests\\Remote";

    function index(){
        $apis = $this->load_files();
        $index = "";
        foreach($apis as $name => $values) {
            if(!in_array($name, __APP_SETTINGS__['API_remote_gateways_enabled'])) continue;
            $index .= "<a href='$name' class='pretty-link'>$values[icon]<span>$values[name]</span></a>";
        }
        
        add_vars([
            'title' => "Supported API Keys",
            'index' => $index
        ]);

        set_template("/admin/api/keys-index.html");
    }

    function key($name) {
        $files = $this->load_files();
        if(!key_exists($name,$files)) throw new NotFound("That does not exist");
        
        $thing = $files[$name]['namespace'] . "\\" . $name;
        
        $api = new $thing();

        $tk = $api->findOne(["token_name" => $api::class]);
        $tkIface = $api->getIfaceName();

        $token = new $tkIface($tk);

        add_vars([
            'title' => $thing::getMetadata()['name'],
            'id' => $name,
            'token' => $tk
        ]);

        $template = "/admin/api/key.html";
        $meta = $thing::getMetadata();
        if(isset($meta['view'])) $template = $meta['view'];

        set_template($template);
    }

    function load_files() {
        $path = "/classes/Cobalt/Requests/Remote";
        $apis = [];
        $this->get_supported_apis(__ENV_ROOT__ . $path, $apis);
        $this->get_supported_apis(__APP_ROOT__ . "/private$path", $apis);

        return $apis;
    }

    function get_supported_apis($dir, array &$array, array $exclude = []) {
        if(!is_dir($dir)) return false;
        $exclude = [".","..", "API.php", "APICall.php", "OAuth.php", ...$exclude];
        $namespace = $this->__namespace;
        $listing = scandir($dir);
        foreach($listing as $li) {
            if(in_array($li,$exclude)) continue;
            $className = pathinfo($li,PATHINFO_FILENAME);
            $array[$className] = array_merge([
                "path" => $li,
                'namespace' => $namespace
            ],
            "$namespace\\$className"::getMetadata()
            );
        }
        return true;
    }

    function update($type) {
        $supported_apis = $this->load_files();
        if(!key_exists($type,$supported_apis)) throw new NotFound("That is not a supported API");
        $name = $supported_apis[$type]["namespace"] . "\\" . $type;
        $manager = new $name();

        $map = $manager->getValidSubmitData();
        $mutant = [];
        $submit = $_POST;
        foreach($map as $key) {
            if(!key_exists($key, $submit)) $submit[$key] = null;
            $mutant[$key] = $submit[$key];
        }
        

        $result = $manager->updateOne([
            'token_name' => $manager::class
        ], [
            '$set' => $mutant
        ], [
            'upsert' => true
        ]);
        
        try {
            $error = $manager->testAPI();
        } catch(\Exception $e) {
            $error = true;
        }

        if($error == false) throw new BadGateway("Your tokens were saved but something went wrong when testing your new settings against the API");
        header('X-Status: @success Your settings were saved and testing was successful.');
        return $result;
    }
}
