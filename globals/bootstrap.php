<?php

$db_config = __APP_ROOT__ . "/config/config.php";

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
function db_cursor($collection, $database = null, $returnClient = false) {
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
        if($returnClient) return $client;
    } catch (Exception $e) {
        die("Cannot connect to database");
    }
    $database = $client->{$database};
    return $database->{$collection};
}

function set_up_db_config_file(string $database, string $user, string $password, string $addr = "localhost", string $port = "27017", string $ssl = "false", string $sslFile = "", string $invalidCerts = "false") {
    file_put_contents($GLOBALS['db_config'],"
<?php
/**
 * This is the bootstrap config file. We use this to
 * Set up our database access. This file is read every
 * time the app is instantiated.
 */

\$GLOBALS['CONFIG'] = [
    'db_driver'      => 'MongoDB', // The Cobalt Engine database driver to use to access the database (MongoDB is the only supported driver)
    'db_addr'        => '$addr', // The database's address
    'db_port'        => '$port', // The database port number
    'database'       => '$database', // The name of your app's database
    'db_usr'         => '$user', // The username for your database
    'db_pwd'         => '$password', // The password for your database
    'db_ssl'         => $ssl, // Enable SSL communication between the app and database
    'db_sslFile'     => '$sslFile', // The SSL cert file for communicating with the database
    'db_invalidCerts'=> $invalidCerts, // Allow self-signed certificates
];"
);
}