<?php

namespace Cobalt\WebSockets\Interfaces;

use Socket;

abstract class Client {
    private Socket $socket;
    private int $socket_id;
    private string $identity_public;
    private string $identity_private;

    public function getSocket():Socket {
        return $this->socket;
    }
    public function setSocket(Socket $socket) {
        $this->socket = $socket;
    }

    public function getSocketId() {
        return $this->socket_id;
    }
    public function setSocketId(int $socket_id) {
        $this->socket_id = $socket_id;
    }

    public function getIdentity() {
        return $this->identity_public;
    }

    public function generateIdentity() {
        $this->identity_public  = random_string(18);
        $this->identity_private = password_hash($this->identity_public, PASSWORD_BCRYPT);
    }

    public function validateIdentity(string $public_key):bool {
        return password_verify($public_key, $this->identity_private);
    }

}