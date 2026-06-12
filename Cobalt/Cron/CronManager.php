<?php
namespace Cobalt\Cron;

use Cobalt\Model\Traits\Accessible;
use Error;
use Exception;
use Override;

/**
 * CronManager handles Cobalt- and Application-defined Tasks that are to be run
 * at regular intervals.
 * @package Cobalt\Cron
 */
class CronManager {
    use Accessible;
    const COBALT_CORE_TASK_FILE = __DIR__ . "/core_tasks.php";
    const COBALT_APP_TASK_FILE  = __APP_ROOT__ . "/config/cron/tasks.php";

    #[Override]
    public function getCollectionName($string = null): string {
        return "CobaltCronTasks";
    }

    /**
     * @return array<ICronTask>
     */
    function load_tasks():array {
        $tasks = $this->get_task_file(self::COBALT_CORE_TASK_FILE);
        $tasks += $this->get_task_file(self::COBALT_APP_TASK_FILE, false);
        return $tasks;
    }

    /**
     * @return array<ICronTask>
     */
    private function get_task_file(string $path, bool $required = true):array {
        if(!file_exists($path)) {
            if($required) throw new Exception("Failed to load required Crontask file `$path`");
            return [];
        }
        $tasks = include $path;
        if(!is_array($tasks)) throw new Exception("This the Crontask file `$path` appears to be malformed.");
        foreach($tasks as $task) {
            $this->validate_task($task);
        }
        return $tasks;
    }

    private function validate_task(mixed $task) {
        if($task instanceof ICronTask === false) throw new Exception($task::class . " must implement 'Cobalt\Cron\ICronTask'");
    }

    function execute() {
        try {
            $tasks = $this->load_tasks();
        } catch(Exception $e) {
            cobalt_log('TASK_EXEC', "Failed to load task files: ". $e->getMessage());
            return;
        }
        foreach($tasks as $task) {
            // $details = $task->crontask_details();
            $this->runCronTask($task);
        }
    }

    function runCronTask(ICronTask $task) {
        $start = microtime(true);
        try {
            $task->crontask_setup();
            $status = $task->crontask_execute($this);
            $task->crontask_post($status);
            $end = microtime(true);
        } catch (Exception $e) {
            $end = microtime(true);
            cobalt_log('TASK_EXEC', $task::class . " failed with the following exception: ". $e->getMessage());
            return;
        } catch (Error $e) {
            cobalt_log('TASK_EXEC', $task::class . " failed with the following error: ". $e->getMessage());
            return;
        }

        if($status >= 1) {
            cobalt_log('TASK_EXEC', $task::class . " failed with the following failure state: ".$task->crontask_status_lookup($status));
        } else {
            cobalt_log('TASK_EXEC', $task::class . " completed successfully in ".($start - $end)." seconds");
        }
    }
}