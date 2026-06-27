<?php

use Cobalt\Auth\Commands\Users;
use Cobalt\Commands\CommandParser;
use Cobalt\Commands\Native\App;
use Cobalt\Commands\Native\Help;
use Cobalt\DBManagement\Commands\DB;
use Cobalt\JobQueue\Commands\Jobs;
use Cobalt\Settings\Commands\SettingsCommand;

return [
    'help' => new Help(),
    'app'  => new App(),
    'db'   => new DB(),
    'jobs' => new Jobs(),
    'settings' => new SettingsCommand(),
    'user' => new Users(),
];