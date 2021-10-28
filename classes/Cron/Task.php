<?php

namespace Cron;

class Task {
    function __construct($task, $date) {
        $this->task = $task;
        $this->date = $date;
    }

    public function init() {
    }

    public function run() {
        $class = $this->task['class'];
        $method = $this->task['method'];

        $instance = new $class(...array_values($this->task['class_args']));
        return $instance->{$method}(...array_values($this->task['method_args']));
    }

    public function log_message() {
        return "No message.";
    }
}
