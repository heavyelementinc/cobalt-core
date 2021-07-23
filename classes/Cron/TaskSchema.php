<?php

namespace Cron;

use Validation\Exceptions\ValidationIssue;

class TaskSchema extends \Validation\Normalize {

    public function __get_schema(): array {
        return [
            'type' => [
                'set' => fn () => 'DefaultType',
            ],
            'name' => [],
            'class' => [],
            'class_args' => [
                'set' => 'args'
            ],
            'method' => [],
            'method_args' => [
                'set' => 'args',
            ],
            'interval' => [
                'get' => fn ($val) => $val,
                'set' => fn ($val) => $val,
            ],
        ];
    }

    public function args($val) {
        return (is_array($val)) ? $val : throw new ValidationIssue("Must be an array");
    }
}
