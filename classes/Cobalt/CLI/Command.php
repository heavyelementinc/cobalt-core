<?php

namespace Cobalt\CLI;

interface Command {
    

    function main(): void;

    /**
     * The return value must have a list of commands like so:
     * [
     *    'subcommand' => [
     *       'description' => 'Some help text that describes the subcommand',
     *       'arguments' => [
     *           'arg1' => 'Description',
     *           'arg2' => 'Description',
     *       ],
     *    ]
     * ]
     * @return array 
     */
    function reference(): array;
}