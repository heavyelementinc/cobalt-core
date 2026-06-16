<?php

use Cobalt\Commands\Exceptions\CommandError;

$GLOBALS['built_in_flags'] = [
    'h' => [
        'description' => "Provides help text",
        'function' => function ($v) {
            if(empty($_SERVER['command'])) {
                array_unshift($_SERVER['command'], 'help', 'list');
                return;
            }
            $cmd = 'command';
            if($v === 'list') $cmd = 'list';
            array_unshift($_SERVER['command'], 'help', $cmd);
        }
    ],
    'plain' => [
        'description' => 'Disables output of control characters in CLI'
    ],
    'debug-exception' => [
        'description' => 'Throws an exception',
        'function' => fn ($v) => throw new Exception("Test message")
    ],
    'debug-error' => [
        'description' => 'Throws an error',
        'function' => fn ($v) => throw new Error("Test message")
    ],
    'debug-commanderror' => [
        'description' => 'Throws a CommandError',
        'function' => fn ($v) => throw new CommandError("Test message")
    ]
];

foreach(flags() as $flag => $value) {
    if(!key_exists($flag, $GLOBALS['built_in_flags'])) continue;
    if(!key_exists('function', $GLOBALS['built_in_flags'][$flag])) continue;
    call_user_func($GLOBALS['built_in_flags'][$flag]['function'],$value);
}

// Check if there are no commands set or if there's only one command set.
// If conditions are met, pretend the -h flag is set.
switch(count($_SERVER['command'])) {
    case 0:
        call_user_func($GLOBALS['built_in_flags']['h']['function'],'list');
        break;
    case 1:
        call_user_func($GLOBALS['built_in_flags']['h']['function'],'list');
        break;
}