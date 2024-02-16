<?php

class Websocket {
    public $help_documentation = [
        'create' => [
            'description' => "[firstname [username [password [email]]]] - Create new user. Password cannot contain spaces if called as single command.",
            'context_required' => true
        ],
    ];

    function init() {
        $manager = new \Cobalt\WebSocket\MessageHandler();
        $loop = new \Cobalt\WebSocket\Loop($manager, app("domain_name"), 443);
        $loop->execute();
    }
}
