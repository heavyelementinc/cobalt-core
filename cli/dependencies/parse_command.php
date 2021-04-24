<?php

// core.sh project init --something -something-else 
// ["core.sh","project","init","--something"]
$commands = $argv;
require_once __CLI_ROOT__ . "/dependencies/Command.php";

array_shift($commands); // ["project","init","--something"]
$lower = array_shift($commands);  // $lower = "project"; $command = ["init","--something"];
$cmd = ucfirst($lower); // Uppercase the FIRST LETTER of the command

$subcmd = array_shift($commands); // $subcmd = "init";   $command = ["--something"];

if(empty($cmd)) {
    say("Cobalt Engine command line tool. Find a list of available commands here:");
    $lower = "help";
    $cmd = "Help";
    $subcmd = "all";
}

$cmd_file = __CLI_ROOT__ . "/commands/$cmd.php";
if(!file_exists($cmd_file)){
    say("Unrecognized command","e");
}

require_once $cmd_file;

$class = new $cmd();

if(empty($subcmd)){
    say("Invalid operand","e");
    exit;
}
if(!method_exists($class,$subcmd) && $cmd !== "Help") {
    say("Unrecognized command", "e");
    exit;
}

$result = $class->{$subcmd}(...$commands); // 

if(gettype($result) === "string") print($result);
print("\n");
exit;