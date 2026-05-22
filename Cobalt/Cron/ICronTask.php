<?php
namespace Cobalt\Cron;
interface ICronTask {
    /**
     * @return array{name:string}
     */
    // function crontask_details():array;
    function crontask_status_lookup(int $status):string;
    function crontask_setup():void;
    function crontask_execute(CronManager $manager):int;
    function crontask_post(int &$status):void;
}