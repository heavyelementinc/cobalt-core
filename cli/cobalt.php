<?php

/** CHECK PHP VERSION */

if( version_compare(phpversion(), '7.3.0', '<=') ) die("Your version of PHP must be version 7.4 or above.");

define('__CLI_ROOT__', __DIR__ . "/dependencies/");

/** Import our helper functions */
require __CLI_ROOT__ . "/helper_functions.php";
require __CLI_ROOT__ . "/command_functions.php";
require __CLI_ROOT__ . "/cli_loop.php";