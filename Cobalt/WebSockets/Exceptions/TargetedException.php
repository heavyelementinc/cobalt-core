<?php
namespace Cobalt\Websockets\Exceptions;

use Exception;
use Throwable;

class TargetedException extends SocketException {
    private int $to;
    function __construct(int $to, string $message, string $type = "exception", string $command = "", array $args = [], int $code = 0, Throwable|null $previous) {
        parent::__construct($message, $type, $command, $args, $code, $previous);
    }

    function getTo():int {
        return $this->to;
    }
    function setTo(int $to) {
        $this->to = $to;
    }
}
