<?php

function cli_parse_args(){
    $args = $argv;
    array_shift($args);
    $arguments = [];
    foreach($args as $i => $arg){
        $values = explode("=",$arg);
        $arguments[str_replace('--','__',$values[0])] = $values[1];
    }
    return $arguments;
}

function readline_parse($input){
    $pos = explode(' ',$input);
    $command = array_shift($pos);
    $args = implode(' ',$pos);
    if(!empty($args)) $args = json_decode("[$args]",true);
    else $args = [];
    return ['command' => $command, 'args' => $args];
}

function dbg($var){
    return false;
    print(json_encode($var,JSON_PRETTY_PRINT) . "\n");
}

function cli_to_bool($input,$defaultToYes = false){
    $allowed = ['y','yes','true','on','enable','enabled'];
    if($defaultToYes) array_push($allowed,"");
    return in_array(strtolower($input),$allowed);
}