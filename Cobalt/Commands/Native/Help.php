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

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }

    public function list($commandName = null):int {
        say("Cobalt Engine v".__COBALT_VERSION, "s");
        if(!$commandName) $commandName = "all";
        if($commandName === "all") {
            say("Listing all known commands");
            return $this->list_all_commands();
        }
        $commands = $GLOBALS['parser']->get_commands();
        if(!key_exists($commandName, $commands)) {
            say("Unknown command '$commandName'");
            return 1;
        }
        return $this->list_command_group($commandName, $commands[$commandName]);
    }

    private function list_all_commands():int {
        /** @var array $commands $ */
        $commands = $GLOBALS['parser']->get_commands();
        /** @var CommandInterface $command */
        foreach($commands as $commandName => $command) {
            $this->list_command_group($commandName, $command);
        }
        return COBALT_COMMAND_SUCCESS;
    }

    private function list_command_group(string $commandName, CommandInterface $cmd):int {
        $validCommands = $cmd->validCommands();
        print("\n[".fmt($commandName,"b")."]\n");
        
        /** @var CommandItem $commandItem */
        foreach($validCommands as $commandMethod => $commandItem) {
            $this->list_command($commandItem, $validCommands->getMaxCommandCharLenth());
        }
        return COBALT_COMMAND_SUCCESS;
    }
    
    private function list_command(CommandItem $item, $tab_padding = 0) {
        $item->renderCommandDetails($tab_padding);
    }
}