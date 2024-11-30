<?php

use Cobalt\Tasks\TaskManager;
use Controllers\Controller;
use Webmention\WebmentionHandler;

class Webhooks extends Controller {

    function __construct() {

    }

    /**
     * This route method accepts incoming Webmentions and queues them for processing
     * @return never 
     * @throws TypeError 
     */
    function linkback() {
        $taskMan = new TaskManager();
        $task = $taskMan->task();
        $task->set_class(new WebmentionHandler());
        $task->set_method("process_task");
        $task->set_additional_data($_POST);
        $task->set_timer(2);
        $taskMan->queue_item($task);
        cobalt_log("LINKBACK", "Scheduling linkback handling from $_POST[source]");
        header("HTTP/1.1 202 Accepted");
        exit;
    }
}
