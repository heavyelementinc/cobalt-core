<?php
error_reporting(E_ALL && ~E_WARNING && ~E_NOTICE);

// core.sh project init --something -something-else 
// ["core.sh","project","init","--something"]
$commands = $argv;
require_once __CLI_ROOT__ . "/dependencies/Command.php";
require_once __CLI_ROOT__ . "/dependencies/process_flags.php";

array_shift($commands); // ["project","init","--something"]
$lower = array_shift($commands);  // $lower = "project"; $command = ["init","--something"];
$cmd = ucfirst($lower); // Uppercase the FIRST LETTER of the command

$subcmd = array_shift($commands); // $subcmd = "init";   $command = ["--something"];

if (empty($cmd)) {
    say(" Cobalt Engine command line tool. Find a list of available commands here:");
    $lower = "help";
    $cmd = "Help";
    $subcmd = "all";
}

$cmd_file = __CLI_ROOT__ . "/commands/$cmd.php";
if (!file_exists($cmd_file)) {
    if (!defined("__APP_ROOT__")) {
        say("Unrecognized command", "e");
        exit;
    }
    $cmd_file = __APP_ROOT__ . "/cli/commands/$cmd.php";
    if (!file_exists($cmd_file)) {
        say("Unrecognized command", "e");
        exit;
    }
}

log_item("Loading command dependency");
require_once $cmd_file;

log_item("Instanitating dependency");
$class = new $cmd();

/** If our subcommand is empty, then we need to print out the help documentation
 * for that command.
 * 
 * @todo Add a way to specify a default command 
 */
if (empty($subcmd)) {
    require_once __CLI_ROOT__ . "/commands/Help.php";
    $class = new Help();
    $subcmd = strtolower($cmd);
    $cmd = "Help";
}
if (!method_exists($class, $subcmd) && $cmd !== "Help") {
    say("Unrecognized command", "e");
    exit;
}

$context_failed = false;

// Check if the subcommand is in the help documentation
if (isset($class->help_documentation[$subcmd])) {
    log_item("Subcommand found in documentation", 2);
    // Set usable variable to the help documentation
    $doc = $class->help_documentation[$subcmd];

    // Check if context is required and if it hasn't been defined
    if (!key_exists('context_required', $doc) && !defined("__APP_ROOT__")) $context_failed = true;
    else if (isset($doc['context_required']) && $doc['context_required']) {
        // Check if the field exists, context is required
        if (!defined("__APP_ROOT__")) $context_failed = true; // Confirm that context is NOT set
    }
}

// Check if context failed and do something about it!
if ($context_failed === true) {
    say("App context is required to perform that action. (Use the --app=<project> flag)", 'e');
    exit;
}
log_item("Executing command with " . count($commands) . " arguments...");
try {
    $result = $class->{$subcmd}(...$commands);
} catch (\Validation\Exceptions\ValidationFailed $e) {
    say($e->getMessage(), "e");
    say(str_replace(["{\n", "\n}"], "", json_encode($e->data, JSON_PRETTY_PRINT)), "e");
    exit;
} catch (Exception $e) {
    say("ABORTING: " . $e->getMessage(), "e");
    exit;
}

if (gettype($result) === "string") print($result);
log_item("Exiting");
print("\n");
exit;
