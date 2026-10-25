<?php

namespace Cobalt\Cron\Commands;

use Cobalt\Commands\Attributes\AcceptsFlags;
use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandList;
use Override;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Cron\CronManager;
use Cobalt\Tasks\Cron\ICronTask;

class Cron extends CommandInterface {
    const UNKNOWN_TASK_NAME = 1;
    private function getInstance():CronManager {
        return new CronManager();
    }
    
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, "list", "listTasks"));
        $list->add(new CommandItem($this, "run:all","runAllTasks"));
        $list->add(new CommandItem($this, "run:task", "runIndividualTask"));
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
    
    #[Description("Run all pending commands")]
    public function runAllTasks() {
        $cron = $this->getInstance();
        $cron->execute();
        return 0;
    }

    #[Description("Run an individual command by name")]
    #[AcceptsFlags('-f - Force the command to run regardless of the last time it was run.')]
    public function runIndividualTask(string $commandName) {
        $cron = $this->getInstance();
        $tasks = $cron->load_tasks();
        foreach($tasks as $task) {
            $details = $task->crontask_details();
            if($details['name'] !== $commandName) continue;
            $cron->runCronTask($task);
            return COBALT_COMMAND_SUCCESS;
        }
        return self::UNKNOWN_TASK_NAME;
    }

    #[Description("List tasks")]
    public function listTasks() {
        $cron = $this->getInstance();
        $tasks = $cron->load_tasks();
        $maxLength = 0;
        $maxDuration = 0;
        print(fmt("\nListing Cron Tasks\n", "b"));
        
        foreach($tasks as $i => $task) {
            $t = $task->crontask_details();
            $maxLength = max($maxLength, strlen($t['name']));
            $maxDuration = max($maxDuration, strlen("$t[frequency]"));
        }

        foreach($tasks as $i => $task) {
            $t = $task->crontask_details();
            $length = $length + 2 - strlen($t['name']);
            $name = fmt($t['name'],'i') . str_pad("", $length, " ");
            $frequency = fmt("Every " . str_pad($t['frequency'], $maxDuration, " ") . " seconds", "grey");
            $lastRun = "Never run";
            print(" - $name  $frequency  $lastRun\n");
        }
        return 0;
    }
}