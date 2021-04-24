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

function cli_parse_input($string){
    $raw = explode(" ",$string);
    $args = [];
    
    for($i = 0; $i <= count($raw); $i++){
        // $GLOBALS['cobalt_cli_commands']

    }

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

function confirm_message($message,$default = false,$additional = ""){
    $auto_prompt = "y/N";
    $default_to_yes = false;
    if(cli_to_bool($default) || $default === true) {
        $auto_prompt = "Y/n";
        $default_to_yes = true;
    }
    $question = readline("$message ($default): ");
    return cli_to_bool( $question, $default_to_yes );
}

function say($str,$type = "normal",$formatted = false){
    $fmt = fmt($str,$type);

    if($formatted !== false) printf($fmt . " \n",$formatted);
    print($fmt . " \n");
}

function fmt($str,$type = "normal"){
    $fmt = "";
    switch($type){
        case "b":
            $fmt = "\033[1m$str\033[0m";
        break;
        case 'e': //error
            $fmt = "\033[31m$str\033[0m";
        break;
        case 's': //success
            $fmt = "\033[32m$str\033[0m";
        break;
        case 'w': //warning
            $fmt = "\033[33m$str\033[0m";
        break;  
        case 'i': //info
            $fmt = "\033[36m$str\033[0m";
        break;
        case "normal":
        default:
            $fmt = $str . "";
    }
    return $fmt;
}