<?php

namespace Cobalt\Commands\Classes;

use Cobalt\Commands\Traits\ErrorHandler;

abstract class CommandInterface {
    // use ErrorHandler;
    abstract function validCommands():CommandList;

    abstract function handleFlags(array $flags, CommandItem $item, string $method, array $arguments):int;
}