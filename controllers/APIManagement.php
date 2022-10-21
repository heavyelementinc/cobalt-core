<?php

use Exceptions\HTTP\NotFound;

class APIManagement {

    var $__namespace = "\\Cobalt\\Requests\\Remote";

    function index(){
        $apis = $this->load_files();
        $index = "";
        foreach($apis as $name => $values) {
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
        $token = iterator_to_array($api->authorizationToken());

        if(!is_root()) {
            $token['secret'] = "";
            $token['token'] = "";
        }

        add_vars([
            'title' => $thing::getMetadata()['name'],
            'id' => $name,
            'token' => $token
        ]);

        set_template("/admin/api/key.html");
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
        $exclude = [".","..", "API.php", "APICall.php", ...$exclude];
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

        $mutant = [
            'key'    => $_POST['key'],
            'secret' => $_POST['secret'],
            'token'    => $_POST['token'],
            'type'   => $_POST['authorization'],
            'prefix' => $_POST['prefix'],
            'expiration' => $_POST['expiration'],
        ];

        $result = $manager->updateToken($mutant);
        
        return $result;
    }
}