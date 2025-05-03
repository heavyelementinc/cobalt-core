<?php
namespace Cobalt\Websockets\Exceptions;

use Exception;
use Throwable;

class SocketException extends Exception {
    private string $type = "exception";
    private string $command = "";
    private array $command_args = [];
    private int $socket_id;
    function __construct(int $socket_id, string $message, string $type = "exception", string $command = "", array $args = [], int $code = 0, Throwable|null $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->setSocketId($socket_id);
        $this->setType($type);
        $this->setCommand($command, $args);
    }

    function getSocketId():int {
        return $this->socket_id;
    }

    function setSocketId(int $socket_id) {
        $this->socket_id = $socket_id;
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