<?php

namespace Cobalt\WebSocket;

/**
 * The MessageHandler class acts as the logic for the WebSocket. Provide callback
 * functions for each type of WebSocket event and the loop will call the appropriate
 * callback as appropriate.
 * @package Cobalt\WebSocket
 */
abstract class MessageHandler {

    protected Loop $loop; 

    abstract public function onOpen(Client $conn);
    abstract public function onMessage($from, $message):Recipients;
    abstract public function onClose(Client $conn);
    abstract public function onError(Client $conn, \Exception $e);

    public function setLoopInstance(Loop $loop) {
        $this->loop = $loop;
    }
}
