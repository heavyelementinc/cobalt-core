<?php

namespace Cobalt\WebSocket;

class Recipients implements \Iterator, \Countable {
    public $clients = [];
    protected int $index = 0;
    protected Loop $loop;

    function __construct($loop) {
        $this->loop = $loop;
    }

    function by_permission($permission) {
        $mutant = [];
        foreach($this->loop->clientSocketArray as $client) {
            if(has_permission($permission, null, $client->user, false)) $mutant[] = &$client;
        }
        $this->clients = $mutant;
    }

    function all() {
        $this->clients = &$this->loop->clientSocketArray;
    }

    public function current(): mixed {
        return $this->clients[$this->index];
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        return $this->index;
    }

    public function valid(): bool {
        return (count($this->clients) - 1 <= $this->index);
    }

    public function rewind(): void {
        $this->index = 0;
    }

    public function count(): int {
        return count($this->clients);
    }
}
