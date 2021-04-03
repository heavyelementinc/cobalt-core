<?php

/** CHECK PHP VERSION */

if( version_compare(phpversion(), '7.3.0', '<=') ) die("Your version of PHP must be version 7.4 or above.");

/** Import our helper functions */
require __DIR__ . "/cobalt_cli_helper_functions.php";
require __DIR__ . "/cobalt_cli_command_functions.php";

$GLOBALS['readline_intro'] = "";
print("\nWelcome to Cobalt CLI - (c)" . date("Y") . " All Rights Reserved - Heavy Element, Inc.\n");
print("\nType help for options!\n\n");

while(true){
    $command = readline("$GLOBALS[readline_intro] > ");
    $cmd = readline_parse($command);
    dbg($cmd);
    if(!key_exists($cmd['command'],$GLOBALS['cobalt_cli_commands'])){
        print("Command `$cmd[command]` not recognized\n");
        continue;
    }
    if(!is_callable($GLOBALS['cobalt_cli_commands'][$cmd['command']]['callback'])) {
        print("Command was recognized, but the callback was not a function\n");
        continue;
    }
    /** Execute the command */
    $result = $GLOBALS['cobalt_cli_commands'][$cmd['command']]['callback'](...$cmd['args']);
    print("$result\n");
}