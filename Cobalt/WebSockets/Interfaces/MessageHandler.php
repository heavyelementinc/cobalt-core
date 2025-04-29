<?php

namespace Cobalt\WebSockets\Interfaces;

use Cobalt\WebSockets\WebSocketServer;

interface MessageHandler {
    function setServer(WebSocketServer $server):void;
    function initialize():void;
    function getClientObject():Client;
    function onMessage(int $socket_id, array &$data, bool &$broadcast_to_all_players):void;
    function onClientJoin(int $socket_id):bool;
    function onClientLeave(int $socket_id):bool;
    function onEveryGameTick(int $tick):void;
}