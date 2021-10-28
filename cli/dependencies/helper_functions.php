<?php

function cli_parse_args() {
    $args = $argv;
    array_shift($args);
    $arguments = [];
    foreach ($args as $i => $arg) {
        $values = explode("=", $arg);
        $arguments[str_replace('--', '__', $values[0])] = $values[1];
    }
    return $arguments;
}

function cli_parse_input($string) {
    $raw = explode(" ", $string);
    $args = [];

    for ($i = 0; $i <= count($raw); $i++) {
        // $GLOBALS['cobalt_cli_commands']

    }
}

function readline_parse($input) {
    $pos = explode(' ', $input);
    $command = array_shift($pos);
    $args = implode(' ', $pos);
    if (!empty($args)) $args = json_decode("[$args]", true);
    else $args = [];
    return ['command' => $command, 'args' => $args];
}

function dbg($var) {
    return false;
    print(json_encode($var, JSON_PRETTY_PRINT) . "\n");
}

function cli_to_bool($input, $defaultToYes = false) {
    $allowed = ['y', 'yes', 'true', 'on', 'enable', 'enabled'];
    if ($defaultToYes) array_push($allowed, "");
    return in_array(strtolower($input), $allowed);
}

function confirm_message($message, $default = false, $additional = "") {
    $auto_prompt = "y/N";
    $default_to_yes = false;
    if (cli_to_bool($default) || $default === true) {
        $auto_prompt = "Y/n";
        $default_to_yes = true;
    }
    $question = readline("$message ($auto_prompt): ");
    return cli_to_bool($question, $default_to_yes);
}

/**
 * Available types:
 *   * `b` - Bold
 *   * `e` - Error
 *   * `s` - Success
 *   * `w` - Warning
 *   * `i` - Information
 *   * `white` - White
 *   * `grey` - Grey
 *   * `normal` - [default] default color
 * @param mixed $str 
 * @param string $type 
 * @param bool $formatted 
 * @return void 
 */
function say($str, $type = "normal", $formatted = false) {
    $fmt = fmt($str, $type);

    if ($formatted !== false) printf($fmt . " \n", $formatted);
    print($fmt . " \n");
}

/**
 * Available types:
 *   * `b` - Bold
 *   * `e` - Error
 *   * `s` - Success
 *   * `w` - Warning
 *   * `i` - Information
 *   * `white` - White
 *   * `grey` - Grey
 *   * `normal` - [default] default color
 * @param mixed $str 
 * @param string $type 
 * @param bool $formatted 
 * @return void 
 */
function fmt($str, $type = "normal", $back = "normal") {
    $fmt = "";
    $arr = [
        'b' => '1m'
    ];


    switch ($type) {
        case "b":
            $fmt = "1m";
            break;
        case 'e': //error
            $fmt = "31m";
            break;
        case 's': //success
            $fmt = "32m";
            break;
        case 'w': //warning
            $fmt = "33m";
            break;
        case 'i': //info
            $fmt = "36m";
            break;
        case 'white':
            $fmt = "1;37m";
            break;
        case 'grey':
            $fmt = "37m";
            break;
        case "bblack":
            $fmt = "1;30m\033[107m";
            break;
        case "normal":
        default:
            $fmt = "39m";
    }
    switch ($back) {
        case "red":
            $bg = "\033[41m";
            break;
        case "green":
            $bg = "\033[42m";
            break;
        case "blue":
            $bg = "\033[44m";
            break;
        case "normal":
        default:
            $bg = "";
    }
    return "\033[$fmt$bg$str\033[0m";
}

function log_item($message, $lvl = 1, $type = "grey", $back = "normal") {
    if ($lvl > $GLOBALS['cli_verbosity']) return;
    $m = fmt("[LOG $lvl]", 'i');
    $m .= " " . fmt($message, $type, $back);
    print($m . "\n");
}
