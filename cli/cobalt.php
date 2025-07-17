<?php
if(posix_getuid() === 0) die("\033[31mThe CLI must not be run as root!\033[0m\n");

/** CHECK PHP VERSION */
if( version_compare(phpversion(), '8.1.0', '<=') ) die("\033[31mYour version of PHP must be version 8.1 or above. Your version: " . phpversion()."\033[0m\n");

ini_set('xdebug.start_with_request', 'trigger');

define('__CLI_ROOT__', __DIR__);

/** Import our helper functions */
require __CLI_ROOT__ . "/dependencies/helper_functions.php";
require __CLI_ROOT__ . "/dependencies/command_functions.php";

// if(count($argv) <= 1) require __CLI_ROOT__ . "/dependencies/shell_loop.php";
// else 
require __CLI_ROOT__ . "/dependencies/parse_command.php";