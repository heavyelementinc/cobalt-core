<?php

namespace Cobalt\WebSocket;

use Countable;
use Iterator;

/**
 * The ClientList acts as a abstraction layer to add/remove clients and associate
 * new connections with Cobalt Users
 * @package Cobalt\WebSocket
 */
class ClientList implements Iterator, Countable {

    protected int $index = 0;
    protected array $clients = [];

    public function connect(ClientConnection $client) {
        array_push($this->clients, $client);
    }

    public function disconnect(ClientConnection $client) {
        foreach($this->clients as $c) {
            if($client->resourceId !== $c->resourceId) continue;
            unset($this->clients[$c]);
            break;
        }
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
