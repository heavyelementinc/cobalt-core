<?php

namespace Cron;

class Run extends \Drivers\Database {
    protected $app_tasks = __APP_ROOT__ . "/private/config/cron/tasks.json";
    private $task_types = ['DefaultType'];
    private $task_cache = [];
    private $log = [];


    public function get_collection_name() {
        return "cron";
    }
    function __construct() {
        $this->date = time();
        parent::__construct();
    }

    function exec() {
        $tasks = $this->get_tasks();
        if (function_exists("say")) say("$this->due_task_count of $this->total_task_count tasks will be executed.");
        if (!$tasks) return say("No tasks need to be executed", "w");
        foreach ($tasks as $task) {
            $this->task($task);
        }
        $this->log_handler();
    }

    protected function task($task) {
        $task_type = "DefaultType";
        if (in_array($task['type'], $this->task_types)) $task_type = $task['type'];

        $task_name = "\Cron\TaskTypes\\$task_type";
        $metrics = ['start' => microtime(true) * 1000];

        // Instance our class
        $task_instance = new $task_name($task, $this->date);
        $task_instance->init(); // Initialize
        $result = $task_instance->run(); // Execute

        $metrics['end'] = microtime(true) * 1000;
        $task = [
            'task' => $task['name'],
            'microseconds' => $metrics['end'] - $metrics['start'],
            'result' => $result,
            'last_run' => $this->date,
            'log_message' => $task_instance->log_message(),
        ];
        array_push(
            $this->log,
            $task,
        );
        $this->updateOne(['name' => $task['task']], ['$set' => $task]);
    }

    public function get_tasks($type = 'due') {
        if (isset($this->task_cache[$type])) return $this->task_cache[$type];
        // Load our built in tasks
        $builtins = get_json(__DIR__ . "/core.json", true);
        if (is_file($this->app_tasks)) {
            $builtins = [...$builtins, ...array_values(get_json($this->app_tasks, true))];
            // if (function_exists("say")) say("Loaded application tasks");
        }
        $this->total_task_count = count($builtins);

        if ($type === 'all') {
            return $builtins;
        }
        $due = [];
        // Filter out any task which we don't want to run.
        foreach ($builtins as $task) {
            // Check the database for the last time we ran this task
            $result = $this->find(...$this->most_recent_query($task));
            $last = iterator_to_array($result);
            if (empty($last)) $this->insertOne(['name' => $task['name']]);
            if (!isset($last[0]) || $last[0]->last_run + $task['interval'] <= $this->date) {
                array_push($due, $task);
                continue;
            }
        }
        $this->due_task_count = count($due);
        $this->task_cache[$type] = $due;
        return $due;
    }

    public function task_stats($task_name) {
        $result = $this->find(...$this->most_recent_query(['name' => $task_name]));
        $record = iterator_to_array($result)[0];
        if (!$record || !isset($record['last_run'])) return [
            'last_run' => "Never ran",
            'microseconds' => "Never ran"
        ];
        $timestamp = $record['last_run'];
        return [
            'result' => $record->result,
            'last_run' => date("D, y/m/d h:i a", $timestamp),
            'microseconds' => round($record['microseconds'] / 1000, 4) . " seconds"
        ];
    }

    private function most_recent_query($task) {
        return [['name' => $task['name']], ['sort' => ['last_run' => -1], 'limit' => 1]];
    }

    private function log_handler() {
        if (function_exists("say")) say(json_encode($this->log, JSON_PRETTY_PRINT));
        // file_put_contents(__DIR__ . "/log.json", json_encode($this->log));
        $this->insertMany($this->log);
    }
}
