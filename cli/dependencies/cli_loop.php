<?php

$GLOBALS['readline_intro'] = "";
say("Welcome to Cobalt CLI - (c)" . date("Y") . " All Rights Reserved - Heavy Element, Inc.","b");
say("Type help for options!",'i');

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