<?php

namespace Cobalt\Commands\Native;

use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Classes\CommandList;
use Override;

class Help extends CommandInterface {
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, 'list'));
        return $list;
    }

    public function list($commandName = null) {
        if(!$commandName)
        $GLOBALS['parser']->get_commands();
    }

    private function list_all_commands() {

    }

    private function list_command_group(string $commandName, CommandList $list, $tab_padding) {

    }
    
    private function list_command(CommandItem $item, $tab_padding = 0) {

    }
}