<?php

use Cobalt\Commands\CommandParser;
use Cobalt\Commands\Exceptions\CommandError;

if(posix_getuid() === 0) die("\033[31mThe CLI must not be run as root!\033[0m\n");

/** CHECK PHP VERSION */
if( version_compare(phpversion(), '8.1.0', '<=') ) die("\033[31mYour version of PHP must be version 8.1 or above. Your version: " . phpversion()."\033[0m\n");

ini_set('xdebug.start_with_request', 'trigger');
error_reporting(E_ERROR | E_PARSE);
// Remove the calling script name
array_shift($argv);

// Parse the flags we've been handed:
$_SERVER['flags'] = [];
$_SERVER['command'] = [];
foreach($argv as $index => $arg) {
    if(substr($arg,0,2) === "--") {        
        // Create a list of parsed flags
        $split = explode("=", $arg);
        $_SERVER['flags'][substr($split[0], 2)] = $split[1];
        continue;
    }
    if($arg[0] != "-") {
        array_push($_SERVER['command'], $arg);
        continue;
    }
    for($i = 0; $i < strlen($arg); $i++)  {
        if($arg[$i] == "-") continue;
        $_SERVER['flags'][$arg[$i]] += 1;
    }
}

// Determine if we had our app defined by the calling function
if(key_exists('app', $_SERVER['flags'])) {
    // Remove this argument from our argv
    array_shift($argv);
    define("__CLI_ROOT__",$_SERVER['flags']['app']);
} else {
    define("__CLI_ROOT__", __DIR__ . "/../../");
}

define("COBALT_COMMAND_SUCCESS", 0);
define("COBALT_COMMANT_UNKNOWN_ERR", -1);

// Do something with global flags
// require __DIR__ . "/dependencies/global-flags.php";

require __DIR__ . "/dependencies/helper_functions.php";
require __CLI_ROOT__ . "/../cobalt-core/env.php";

$parser = new CommandParser();
$parser->load_files();
try {
    // Do all our flag execution here
    require __DIR__ . "/dependencies/handle_builtin_flags.php";
    
    // Then we handle our command execution
    $result = $parser->exec($_SERVER['command'], $_SERVER['flags']);
    if($result > COBALT_COMMAND_SUCCESS) {
        say("An error occurred", 'e');
    }
} catch(CommandError $err) {
    // Handle special errors gracefully
    say($err->getMessage(), $err->getColor());
} catch(Exception|Error $e) {
    print(fmt($e->getMessage(), 'normal', 'red')."\n");
}