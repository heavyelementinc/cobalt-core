<?php
/**
 * @global int COBALT_BOOTSTRAP_AS_NEEDED - 0
 */
define("COBALT_BOOSTRAP_AS_NEEDED", 0);
// define("COBALT_BOOTSTRAP_");
define("COBALT_BOOSTRAP_ALWAYS",  999);

$db_config = __APP_ROOT__ . "/config/config.php";
if(file_exists(__APP_ROOT__ . "/ignored/DEVELOPMENT") || file_exists(__APP_ROOT__ . "/ignored/DEV")) {
    $db_config = __APP_ROOT__ . "/config/config.development.php";
}

function config() {
    global $CONFIG;
    return $CONFIG;
}

if(file_exists($db_config)) {
    // Load the settings file
    require_once $db_config;
    global $CONFIG;
    if(!$CONFIG) die("Cobalt requires your config.php file to declare \$CONFIG");
    if(config() !== $CONFIG) die("Something went wrong with the bootstrap process");

    // Sanity check functions must return TRUE if valid and FALSE if invalid
    $sanity_check = [
        'db_driver'       => fn ($val) => $val === "MongoDB",
        'db_addr'         => fn ($val) => is_string($val),
        'db_port'         => false,
        'database'        => fn ($val) => !empty($val),
        'db_usr'          => false,
        'db_pwd'          => false,
        'db_ssl'          => false,
        'db_sslFile'      => false,
        'db_invalidCerts' => false,
        'bootstrap_mode'  => fn ($val) => ($val === COBALT_BOOSTRAP_AS_NEEDED || $val === COBALT_BOOSTRAP_ALWAYS),
        'safe_mode'       => fn ($val) => is_bool($val),
        'timezone'        => function ($value) {
            return in_array($value, DateTimeZone::listIdentifiers(DateTimeZone::ALL));
        }
    ];

    // Default values allow the config file to omit any value with the following
    // keys. If key is in the $sanity_check and not in $default_values, then the
    // bootstrap process will die.
    $default_values = [
        'db_usr'          => '',
        'db_pwd'          => '',
        'db_ssl'          => false,
        'db_sslFile'      => '',
        'db_invalidCerts' => false,
        'bootstrap_mode'  => COBALT_BOOSTRAP_AS_NEEDED,
        'safe_mode'       => false,
        'timezone'        => 'America/New_York'
    ];

    foreach($sanity_check as $key => $value) {
        // If the sanity check key is not in config
        if(!key_exists($key, $CONFIG)) {
            // Check if 
            if(!key_exists($key, $default_values)) die("Your config.php file is missing a required key.");
            $CONFIG[$key] = $default_values[$key];
        }
        if($value !== false && is_callable($value) && !$value($CONFIG[$key])) die("config.php validation failed. Key `$key` contains invalid data.");
        // if($CONFIG[$key] == false) unset($CONFIG[$key]);
    }

    date_default_timezone_set($CONFIG['timezone']);
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
    global $CONFIG;
    if (!$database) $database = $CONFIG['database'];
    try {
        $authentication = [
            'username'  => $CONFIG['db_usr'],
            'password'  => $CONFIG['db_pwd'],
            'ssl'       => $CONFIG['db_ssl'],
            'sslCAFile' => $CONFIG['db_sslFile'],
            'sslAllowInvalidCertificates' => $CONFIG['db_invalidCerts']
        ];

        if(!$CONFIG['db_usr']) unset($authentication['username']);
        if(!$CONFIG['db_pwd']) unset($authentication['password']);
        if(!$CONFIG['db_ssl']) unset($authentication['ssl']);
        if(!$CONFIG['db_sslFile']) unset($authentication['sslCAFile']);
        if(!$CONFIG['db_invalidCerts']) unset($authentication['sslAllowInvalidCertificates']);
        $client = new MongoDB\Client("mongodb://{$CONFIG['db_addr']}:{$CONFIG['db_port']}",$authentication);
        if($returnDatabase) return $client->{$database};
        if($returnClient) return $client;
    } catch (Exception $e) {
        die("Cannot connect to database");
    }
    $database = $client->{$database};
    return $database->{$collection};
}
