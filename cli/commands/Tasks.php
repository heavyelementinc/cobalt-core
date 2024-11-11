<?php

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

}