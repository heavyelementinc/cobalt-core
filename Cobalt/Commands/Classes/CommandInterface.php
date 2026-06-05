<?php

namespace Cobalt\Commands\Classes;

abstract class CommandInterface {
    abstract function validCommands():CommandList;
}