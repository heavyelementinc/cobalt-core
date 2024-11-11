<?php

use Cobalt\Tasks\TaskManager;
use Controllers\Controller;
use PEAR2\Services\Linkback\Server as LinkbackServer;
use Webmention\Server;

class Webhooks extends Controller {

    function __construct() {

    }

    /**
     * This route method accepts incoming linkbacks and queues them for processing
     * @return never 
     * @throws TypeError 
     */
    function linkback() {
        $taskMan = new TaskManager();
        $task = $taskMan->task();
        $task->set_class(new Server());
        $task->set_method("process_task");
        $task->set_additional_data($_POST);
        $task->set_timer(60);
        $taskMan->queue_item($task);
        header("HTTP/1.1 202 Accepted");
        exit;
    }
}
