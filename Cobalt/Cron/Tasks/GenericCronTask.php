<?php
namespace Cobalt\Cron\Tasks;
use Cobalt\Cron\Tasks\ICronTask;

abstract class GenericCronTask implements ICronTask {
    public ?array $taskResult;
    function crontask_setup(?array $taskResult):void {
        $this->taskResult = $taskResult;
    }
}