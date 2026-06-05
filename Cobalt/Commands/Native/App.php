<?php

namespace Cobalt\Commands\Native;

use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Classes\CommandList;
use Override;

class App extends CommandInterface {
    public static function validCommands(): CommandList {
        $instance = new static;
        $list = new CommandList();

        $list->add((new CommandItem('config'))
            ->setDescription("Get or set configuration details")
            ->setInstance($instance)
            ->setFunction('config')
        );
        return $list;
    }
    
    function config(string $file, mixed $value = null) {

    }

    function get(string $setting) {

    }
}