<?php
namespace Cobalt\Websockets\Exceptions;

use Exception;
use Throwable;

class SocketException extends Exception {
    private string $type = "exception";
    private string $command = "";
    private array $command_args = [];
    function __construct(string $message, string $type = "exception", string $command = "", array $args = [], int $code = 0, Throwable|null $previous) {
        parent::__construct($message, $code, $previous);
        $this->setType($type);
        $this->setCommand($command, $args);
    }

    function getType():string {
        return $this->type;
    }

    function setType(string $type){
        $this->type = $type;
    }

    function getCommand():string {
        return $this->command;
    }

    function getArgs():array {
        return $this->command_args;
    }

    function setCommand(string $command, array $args){
        $this->command = $command;
        $this->command_args = $args;
    }
}