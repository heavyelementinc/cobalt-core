<?php

use Exceptions\HTTP\NotFound;

class RemoteServices {
    private $supportedApis = [];
    function __construct() {
        
    }

    private function loadServices() {
        $path = "/classes/Cobalt/Remote/Services";
        $apis = [];
        $this->enumerateAvailableApis(__ENV_ROOT__ . $path, $apis);
        $this->enumerateAvailableApis(__APP_ROOT__ . $path, $apis);

        return $apis;
    }

    private function enumerateAvailableApis(string $dir, array &$array, array $exclude = []) {
        $ex = [".","..", ...$exclude];
        $listing = scandir($dir);
        $namespace = '\\Cobalt\\Remote\\Services';
        foreach($listing as $file) {
            if(in_array($file, $ex)) continue;
            $service = $dir . "/$file/service.json" ;
            if(file_exists($service)) $service = json_decode(file_get_contents($service), true);
            $finalNamespace = "$namespace\\$file\\$service[auth]";
            $array[$service['identifier']] = array_merge([
                'path' => $file,
                'namespace' => $finalNamespace
            ], "$finalNamespace"::getMetadata());
        }
    }

    function index() {
        $apis = $this->loadServices();
        $index = "";
        foreach($apis as $name => $values) {
            $index .= "<a href='$name' class='pretty-link'><i name='$values[icon]'></i><span>$values[name]</span></a>";
        }
        add_vars([
            'title' => 'Register API Keys',
            'index' => $index,
        ]);
        return view("/admin/api/keys-index.html");
    }

    function editor($name) {
        $files = $this->loadServices();
        if(!key_exists($name, $files)) throw new NotFound("That's not a valid service");
        $namespace = $files[$name]['namespace'];
        $service = new $namespace();
        add_vars([
            "title" => $files[$name]['name'],
            "identifier" => $files[$name]['identifier'],
            "fields" => $service->renderView()
        ]);
        return view("/admin/api/editor.html");
    }

    function update($name) {
        $services = $this->loadServices();
        if(!key_exists($name, $services)) throw new NotFound("That's not a valid service");
        $namespace = $services[$name]['namespace'];
        $service = new $namespace();
        $result = $service->store($_POST);
        if($result[0]) header("X-Status: @success Enabled service '$name'");
        if($result[1]) header("X-Status: @success Updated service '$name'");
        return $result;
    }
}