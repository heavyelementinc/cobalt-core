<?php

use Cobalt\Auth\Commands\Users;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\CommandParser;
use Cobalt\Commands\Native\App;
use Cobalt\Commands\Native\Help;
use Cobalt\Cron\Commands\Cron;
use Cobalt\DataModel\Commands\ModelTests;
use Cobalt\DBManagement\Commands\DB;
use Cobalt\JobQueue\Commands\Jobs;
use Cobalt\Settings\Commands\SettingsCommand;

/** @return array<string, CommandInterface> */
return [
    'help' => new Help(),
    'app'  => new App(),
    'cron' => new Cron(),
    'db'   => new DB(),
    'jobs' => new Jobs(),
    'settings' => new SettingsCommand(),
    'user' => new Users(),
    // 'model' => new ModelTests(),
];