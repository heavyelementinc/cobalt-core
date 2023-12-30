<?php

function __process_flags(){
    foreach($GLOBALS['commands'] as $i => $cmd){
        if($cmd[0] === "-"){
            // Let's delete the flag from the list of commands if it completes
            if(__execute_flag($cmd)) unset($GLOBALS['commands'][$i]);
        }
    }
    // Reindex the array from 0. This probably isn't required, but nice to do 🤣
    $GLOBALS['commands'] = array_values($GLOBALS['commands']);
}

function __execute_flag($flag){
    $split = explode("=",$flag);
    $cmd = array_shift($split);
    if(key_exists($cmd,$GLOBALS['flags']) && is_callable($GLOBALS['flags'][$cmd]['exe'])){
        try{
            $GLOBALS['flags'][$cmd]['exe'](...$split);
            return true;
        } catch(Exception $e){
            say($e->getMessage(),'e');
            return false;
        }
    }

    return false;
}


$flags = [
    '--app' => [
        'description' => 'Executes the command within the context of the given project. Can be app directory name OR absolute path.',
        'exe' => '__app_context',
    ],
    '--verbose' => [
        'description' => 'Sets the verbosity level of the CLI. Use digits 0 through 2',
        'exe' => '__verbosity',
    ],
    '--plain-output' => [
        'description' => 'Prevents the fmt() function from modifying output',
        'exe' => '__plain_output',
    ],
    '--safe-mode' => [
        'description' => 'Prevents extensions and their associated commands from being loaded',
        'exe' => '__safe_mode',
    ],
];


function __app_context($app = ""){
    if(empty($app)) throw new Exception("App name invalid");

    $file = __CLI_ROOT__ . "/../../";
    $index = "/public/index.php";
    $context_found = false;

    if($app[0] === "/" || $app[0] === ".") {
        if(file_exists($app . $index) && is_dir("$app/../cobalt-core")) $context_found = $app;
        log_item("App context found as absolute or relative path.");
    }
    if(!$context_found && file_exists($file.$app.$index)) {
        log_item("App context found as directory name.");
        $context_found = $file.$app;
    }
    if(!$context_found) throw new Exception("Could not establish context for $app");

    $GLOBALS['cli_app_root'] = $context_found;
    require __CLI_ROOT__ . "/../env.php";
}

$GLOBALS['cli_verbosity'] = 0;
function __verbosity($number){
    $GLOBALS['cli_verbosity'] = (int)$number;
    log_item("Verbosity set to $number");
}
$GLOBALS['fmt_allowed'] = true;
function __plain_output() {
    $GLOBALS['fmt_allowed'] = false;
}
$GLOBALS['safe_mode'] = false;
function __safe_mode() {
    $GLOBALS['safe_mode'] = true;
}

__process_flags();
