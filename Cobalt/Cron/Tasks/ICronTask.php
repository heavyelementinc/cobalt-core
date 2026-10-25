<?php
namespace Cobalt\Cron\Tasks;

use Cobalt\Cron\CronManager;
use CronTrait;

interface ICronTask {
    /**
     * This function returns an array that contains information about this cron task
     * @return array{name:string,frequency:int}
     */
    function crontask_details():array;
    
    /**
     * This method accepts the last cron processing event and handles bootstraping the task
     * @param mixed $taskResult
     * @return void
     */
    function crontask_setup(?array $taskResult):void;

    /**
     * This method returns a descriptive string for each status
     * Use `return match($status)` 
     * @param int $status
     * @return void
     */
    function crontask_status_lookup(int $status):string;

    /**
     * Whatever the cron job must do to complete its task
     * @param CronManager $manager
     * @return void
     */
    function crontask_execute(CronManager $manager):int;

    /**
     * Any cleanup necessary.
     * @param int $status
     * @return void
     */
    function crontask_post(int &$status):void;
}