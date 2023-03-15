<?php
$db_config = __APP_ROOT__ . "/config/config.php";
if(file_exists(__APP_ROOT__ . "/ignored/DEVELOPMENT") || file_exists(__APP_ROOT__ . "/ignored/DEV")) {
    $db_config = __APP_ROOT__ . "/config/config.development.php";
}

if(file_exists($db_config)) {
    // Load the settings file
    require_once $db_config;

    if(!$GLOBALS['CONFIG']) die("Cobalt requires your config.php file to declare \$GLOBALS['CONFIG']");

    $sanity_check = [
        'db_driver'  => fn ($val) => $val === "MongoDB",
        'db_addr'    => fn ($val) => is_string($val),
        'db_port'    => false,
        'database'   => fn ($val) => !empty($val),
        'db_usr'     => false,
        'db_pwd'     => false,
        'db_ssl'     => false,
        'db_sslFile' => false,
        'db_invalidCerts' => false,
    ];

    foreach($sanity_check as $key => $value) {
        if(!key_exists($key, $GLOBALS['CONFIG'])) die("Your config.php file is missing a required key.");
        if($value !== false && is_callable($value) && !$value($GLOBALS['CONFIG'][$key])) die("config.php validation failed. Key `$key` contains an invalid data.");
        // if($GLOBALS['CONFIG'][$key] == false) unset($GLOBALS['CONFIG'][$key]);
    }
}

/**
 * db_cursor
 * The way we establish our database connections
 * 
 * @param string $collection - The name of the collection
 * @param string $database - (Optional) The name of the database
 * @return object
 */
function db_cursor($collection, $database = null, $returnClient = false, $returnDatabase = false) {
    if (!$database) $database = $GLOBALS['CONFIG']['database'];
    try {
        $config = [
            'username'  => $GLOBALS['CONFIG']['db_usr'],
            'password'  => $GLOBALS['CONFIG']['db_pwd'],
            'ssl'       => $GLOBALS['CONFIG']['db_ssl'],
            'sslCAFile' => $GLOBALS['CONFIG']['db_sslFile'],
            'sslAllowInvalidCertificates' => $GLOBALS['CONFIG']['db_invalidCerts']
        ];

        if(!$GLOBALS['CONFIG']['db_usr']) unset($config['username']);
        if(!$GLOBALS['CONFIG']['db_pwd']) unset($config['password']);
        if(!$GLOBALS['CONFIG']['db_ssl']) unset($config['ssl']);
        if(!$GLOBALS['CONFIG']['db_sslFile']) unset($config['sslCAFile']);
        if(!$GLOBALS['CONFIG']['db_invalidCerts']) unset($config['sslAllowInvalidCertificates']);
        $client = new MongoDB\Client("mongodb://{$GLOBALS['CONFIG']['db_addr']}:{$GLOBALS['CONFIG']['db_port']}",$config);
        if($returnDatabase) return $client->{$database};
        if($returnClient) return $client;
    } catch (Exception $e) {
        die("Cannot connect to database");
    }
    $database = $client->{$database};
    return $database->{$collection};
}
