<?php

namespace Cobalt\Commands\Native;

use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Classes\CommandList;
use Cobalt\Commands\CommandParser;
use Cobalt\Commands\Exceptions\CommandError;
use Override;

class Help extends CommandInterface {

    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, 'list'));
        $list->add(new CommandItem($this, 'command','command_details'));
        return $list;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("Lists help info for the specified command group (or all groups if none specified)")]
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

    #[Description("Get the details of a specific command")]
    public function command_details(...$args):int {
        [$commandName, $subcommand] = $args;
        /** @var array<CommandInterface> $listOfCommands */
        $listOfCommands = $GLOBALS['parser']->get_commands();
        if(!key_exists($commandName, $listOfCommands)) throw new CommandError("Command not found");
        say("Cobalt Engine v".__COBALT_VERSION, "s");
        if(!$subcommand) {
            $this->list_detailed_command_group($commandName, $listOfCommands[$commandName]);
            return COBALT_COMMAND_SUCCESS;
        }
        $subcommand_list = $listOfCommands[$commandName]->validCommands();
        $subcommand_item = $subcommand_list->findByCommandName($subcommand);
        print("\n");
        $this->list_detailed_command($commandName, $subcommand, $subcommand_item);
        return COBALT_COMMAND_SUCCESS;
    }

    private function list_detailed_command_group(string $commandName, CommandInterface $cmd) {
        $validCommands = $cmd->validCommands();
        print("\n".fmt("Command details for ".$commandName,"b", "green")."\n\n");
        
        /** @var CommandItem $commandItem */
        foreach($validCommands as $commandMethod => $commandItem) {
            $this->list_detailed_command($commandName, $commandMethod, $commandItem);
        }
        return COBALT_COMMAND_SUCCESS;
    }

    private function list_detailed_command(string $commandName, string $subcommand, CommandItem $command) {
        print("$subcommand - ".$command->getLongDescription()."\n");
        printf("%s %s",fmt($commandName, 'b'), fmt($subcommand, 'i'));
        printf("%s", $command->renderVerboseCommandDetails());
    }
}