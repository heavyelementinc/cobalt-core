<?php

namespace Cobalt\Cron\Commands;

use Cobalt\Commands\Attributes\AcceptsFlags;
use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandList;
use Override;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Cron\CronManager;
use Cobalt\Cron\ICronTask;

class Cron extends CommandInterface {
    private array $flags = [];

    private function getInstance():CronManager {
        return new CronManager();
    }
    
    #[Override]
    public function validCommands(): CommandList
    {
        $list = new CommandList();
        $list->add(new CommandItem($this, "all","new"));
        return $list;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        $this->flags = [
            'f' => 0,
            ...$flags
        ];
        return COBALT_COMMAND_SUCCESS;
    }
    
    public function runAllTasks() {
        $cron = $this->getInstance();
        $cron->execute();
    }

    #[Description("Run an individual command by name")]
    #[AcceptsFlags('-f - Force the command to run regardless of the last time it was run.')]
    public function runIndividualTask(string $commandName) {
        
    }

}