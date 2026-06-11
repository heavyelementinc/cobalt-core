<?php

use Cobalt\Auth\Commands\Users;
use Cobalt\Commands\CommandParser;
use Cobalt\Commands\Native\App;
use Cobalt\Commands\Native\Help;
use Cobalt\DBManagement\Commands\DB;

return [
    'help' => new Help(),
    'app' => new App(),
    'db' => new DB(),
    'user' => new Users(),
];