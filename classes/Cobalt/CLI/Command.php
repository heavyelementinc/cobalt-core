<?php

namespace Cobalt\CLI;

abstract class Command {
    

    abstract function main():int;

    /**
     * The return value must have a list of commands like so:
     * [
     *    'subcommand_name' => [
     *       'description' => 'Some help text that describes the subcommand',
     *       'arguments' => [
     *           'arg1' => 'Description',
     *           'arg2' => 'Description',
     *       ],
     *    ]
     * ]
     * @return array 
     */
    abstract static function reference():array;
}
