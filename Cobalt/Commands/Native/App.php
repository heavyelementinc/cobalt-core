<?php

namespace Cobalt\Commands\Native;

use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Classes\CommandList;
use Exception;
use Override;

class App extends CommandInterface {
    public function validCommands(): CommandList {
        $list = new CommandList();

        $list->add((new CommandItem($this, 'config', 'config'))
            ->setDescription("Get or set configuration details")
        );
        return $list;
    }

    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }
    
    #[Description("Get or set configuration details")]
    function config(string $file, mixed $value = null) {
        throw new Exception("Not implemented");
    }

    function get(string $setting) {
        throw new Exception("Not implemented");
    }
}