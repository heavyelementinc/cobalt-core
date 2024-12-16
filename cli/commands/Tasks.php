<?php

use Cobalt\Tasks\Task;
use Cobalt\Tasks\TaskManager;

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Tasks {
    public $help_documentation = [
        'process' => [
            'description' => "Process task queue",
            'context_required' => true,
        ],
        'count' => [
            'description' => "Count the number of tasks in the queue",
            'context_required' => true
        ],
        'upcoming' => [
            'description' => 'List the next 10 tasks',
            'context_required' => true,
        ]
    ];
    private TaskManager $taskMan;
    function __construct() {
        $this->taskMan = new TaskManager();
    }

    function process() {
        say($this->taskMan->process_queue());
        return;
    }

    function count() {
        $count = $this->taskMan->count($this->taskMan->get_query());;
        say("Tasks in queue: ". fmt($count, "i"));
        return;
    }

    function upcoming($n = 10) {
        // $result = $this->taskMan->find([], ['sort' => ['date' => 1], 'limit' => $n]);
        // $now = "";
        // /** @var Task*/
        // foreach($result as $task) {
        //     $task->
        // }
    }

}