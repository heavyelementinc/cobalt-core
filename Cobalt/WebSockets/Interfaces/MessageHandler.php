<?php

namespace Cobalt\WebSockets\Interfaces;

use Cobalt\WebSockets\TimeOut;
use Cobalt\WebSockets\WebSocketServer;

interface MessageHandler {
    function setServer(WebSocketServer $server):void;
    function initialize():void;
    function getClientObject():Client;
    function onMessage(int $socket_id, array &$data, int $message_id):void;
    function onClientJoin(int $socket_id):bool;
    function onClientLeave(int $socket_id):bool;
    function onConsoleLog(string $log, string $type, string $log_message, $log_type = "i", $verbosity_level = 10):void;
    function onEveryTick(TimeOut $interval):void;
}