<?php

use Cobalt\Commands\CommandParser;
use Cobalt\Commands\Exceptions\CommandError;

if(posix_getuid() === 0) die("\033[31mThe CLI must not be run as root!\033[0m\n");

/** CHECK PHP VERSION */
if( version_compare(phpversion(), '8.1.0', '<=') ) die("\033[31mYour version of PHP must be version 8.1 or above. Your version: " . phpversion()."\033[0m\n");

ini_set('xdebug.start_with_request', 'trigger');
// Disable error reporting so that we don't have unreadable jargon on the screen
error_reporting(E_ERROR | E_PARSE);

define('FLAGS_KEY', 'flags');
define('COMMAND_KEY', 'command');

// Remove the calling script name
array_shift($argv);

// Parse the flags we've been handed:
$_SERVER[FLAGS_KEY] = [];
$_SERVER[COMMAND_KEY] = [];
foreach($argv as $index => $arg) {
    // Detects long flags (--read=path/to/file.php) and splits them into an array
    // like so ['read' => 'path/to/file.php']
    if(substr($arg,0,2) === "--") {        
        // Create a list of parsed flags
        $split = explode("=", $arg);
        $_SERVER[FLAGS_KEY][substr($split[0], 2)] = $split[1];
        continue;
    }
    // If this argument's first character is not a '-' then it's part of the command
    if($arg[0] != "-") {
        // Rebuild our command without the flags by pushing the current non-flag
        // arg to the $_SERVER[COMMAND_KEY]
        array_push($_SERVER[COMMAND_KEY], $arg);
        continue;
    }
    // Loop through each character in the current flag and build a list of values
    // Short flags (-h) can have any number of arbitrary characters after
    // the tack `-hhvt=N` which will result in an array like this:
    //    ['h' => 2, 'v' => 1, 't' => 1, '=' => 1, 'N' => 1]
    // This lets us do `-vvvv` which could set the verbosity mode to 4
    for($i = 0; $i < strlen($arg); $i++) {
        if($arg[$i] == "-") continue;
        $_SERVER[FLAGS_KEY][$arg[$i]] += 1;
    }
}

// We need to handle our context now before we start loading our dependencies
// Determine if we had our app defined by the calling function
if(key_exists('app', $_SERVER[FLAGS_KEY])) {
    // Remove this argument from our argv
    array_shift($argv);
    define("__CLI_ROOT__",$_SERVER[FLAGS_KEY]['app']);
} else {
    define("__CLI_ROOT__", __DIR__ . "/../../");
}

define("COBALT_COMMAND_SUCCESS", 0);
define("COBALT_COMMANT_UNKNOWN_ERR", -1);

// Load our helper functions and the Cobalt environment
require __DIR__ . "/dependencies/helper_functions.php";
require __CLI_ROOT__ . "/../cobalt-core/env.php";

$parser = new CommandParser();
$parser->load_files();
try {
    // Now that we're within the CommandParser context, let's do the rest of our
    // built-in flag setup here
    require __DIR__ . "/dependencies/handle_builtin_flags.php";
    
    // Finally, let's pass off execution to our CommandParser
    $result = $parser->exec($_SERVER[COMMAND_KEY], $_SERVER[FLAGS_KEY]);
    if($result > COBALT_COMMAND_SUCCESS) {
        say("An error occurred", 'e');
    }
} catch(CommandError $err) {
    // Handle special errors gracefully
    say($err->getMessage(), $err->getColor());
} catch(Exception|Error $e) {
    print(fmt($e->getMessage(), 'normal', 'red')."\n");
}