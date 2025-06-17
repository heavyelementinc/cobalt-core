<?php

use Cobalt\WebSockets\Interfaces\MessageHandler;

class Websocket {
    public $help_documentation = [
        'start' => [
            'description' => "[namespaced_message_handler] Initialize the WebSocket",
            'context_required' => true
        ],
    ];

    /** TODO: REFACTOR */
    function start(string $namespaced_message_handler = "") {
        if(!$namespaced_message_handler) $namespaced_message_handler = app("Websocket_default_message_handler");
        if(!$namespaced_message_handler) throw new Exception("Failed to determine fallback message handler");
        
        /** @var MessageHandler */
        $manager = new $namespaced_message_handler();
        if(!$manager instanceof MessageHandler) throw new Exception("Must be instance of Cobalt\\WebSocket\\MessageHandler");

        $loop = new \Cobalt\WebSocket\Loop($manager, app("domain_name"), app("Websocket_default_port"));
        $loop->execute();
    }

}
