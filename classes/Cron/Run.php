<?php

namespace Cron;

class Run extends \Drivers\Database {
    private $task_types = ['DefaultType'];
    private $log = [];

    function __construct() {
        $this->date = time();
        parent::__construct();
    }

    function exec() {
        $tasks = $this->get_tasks();
        foreach ($tasks as $task) {
            $this->task($task);
        }
        $this->log_handler();
    }

    protected function task($task) {
        $task_type = "DefaultType";
        if (in_array($task->type, $this->task_types)) $task_type = $task->type;

        $task_name = "\Cron\TaskTypes\\$task_type";
        $metrics = ['start' => microtime(true) * 1000];

        // Instance our class
        $task_instance = new $task_name($task, $this->date);
        $task_instance->init(); // Initialize
        $result = $task_instance->run(); // Execute

        $metrics['end'] = microtime(true) * 1000;
        array_push(
            $this->log,
            [
                'task' => $task->name,
                'microseconds' => $metrics['end'] - $metrics['start'],
                'result' => $result,
                'last_run' => $this->date,
                'log_message' => $task_instance->log_message(),
            ],
        );
    }

    protected function get_tasks($type = 'due') {
        // Load our built in tasks
        $builtins = get_json(__DIR__ . "/core.json", false);
        if ($type === 'all') {
            return $builtins;
        }
        $due = [];
        // Filter out any task which we don't want to run.
        foreach ($builtins as $task) {
            // Check the database for the last time we ran this task
            $result = $this->find(['name' => $task->name], ['sort' => ['last_run' => -1], 'limit' => 1]);
            $last = iterator_to_array($result);
            if (!isset($last[0]) || $last[0]->last_run + $task->interval <= $this->date) {
                array_push($due, $task);
                continue;
            }
        }
        return $due;
    }

    public function get_collection_name() {
        return "cron";
    }

    private function log_handler() {
        if (function_exists("say")) say(json_encode($this->log, JSON_PRETTY_PRINT));
        // file_put_contents(__DIR__ . "/log.json", json_encode($this->log));
        $this->insertMany($this->log);
    }
}
