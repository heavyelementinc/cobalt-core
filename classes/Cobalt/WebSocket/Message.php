<?php

namespace Cobalt\WebSocket;

class Message {
    public array $content = [];
    function __construct($content) {
        $this->content = $content;
    }

    function sensitive_fields():array {
        return [];
    }

    public function redacted() {
        $mutant = $this->content;
        foreach($this->sensitive_fields() as $sensitive) {
            unset($mutant[$sensitive]);
        }
        return $mutant;
    }
}
