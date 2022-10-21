<?php

/** CHECK PHP VERSION */

if( version_compare(phpversion(), '8.1.0', '<=') ) die("Your version of PHP must be version 8.1 or above. Your version: " . phpversion());

define('__CLI_ROOT__', __DIR__);

/** Import our helper functions */
require __CLI_ROOT__ . "/dependencies/helper_functions.php";
require __CLI_ROOT__ . "/dependencies/command_functions.php";

// if(count($argv) <= 1) require __CLI_ROOT__ . "/dependencies/shell_loop.php";
// else 
require __CLI_ROOT__ . "/dependencies/parse_command.php";