<?php

// We will import the env.cfg file from our __APP_ROOT__
$config_file = __APP_ROOT__ . "/env.cfg";
if(!file_exists($config_file)) migrate_to_conf_file($config_file);

define("__ENV__", parse_ini_file($config_file));

initialize_database_connection();

function migrate_to_conf_file($path) {
    $json = get_json(__APP_ROOT__ . "/private/config/settings.json");
    $required = [
        "db_driver" => ['default' => "MongoDB"],
        "db_addr" => [],
        "database" => [],
        "db_usr" => [],
        "db_pwd" => [],
        "db_ssl" => [],
        "db_sslFile" => [],
        "db_sslAllowSelfSignedCerts" => [],
    ];

    $defaults = get_json(__ENV_ROOT__ . "/config/setting_default.jsonc");
    $settings = [];

    foreach($required as $i => $v) {
        if(isset($json[$i])) $settings[$i] = $json[$i];
        elseif(isset($v['default'])) $settings[$i] = $v['default'];
        elseif(isset($defaults[$i])) $settings[$i] = $defaults['default'][$i];
        else $settings[$i] = "";
    }

    file_put_contents($path,serialize_array_to_conf_file($settings));
}

function serialize_array_to_conf_file($arr) {
    $string = "";
    foreach($arr as $f => $v) {
        $string .= "$f=\"$v\"\n";
    }
    return $string;
}

function initialize_database_connection(){
    $GLOBALS['DATABASE_CONNECTION'] = new \MongoDB\Client(__ENV__['database_uri']);
}